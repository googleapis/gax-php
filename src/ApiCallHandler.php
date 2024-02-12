<?php
/*
 * Copyright 2024 Google LLC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Google\ApiCore;

use Google\ApiCore\Descriptor\MethodDescriptor;
use Google\ApiCore\Descriptor\ServiceDescriptor;
use Google\ApiCore\Middleware\CredentialsWrapperMiddleware;
use Google\ApiCore\Middleware\FixedHeaderMiddleware;
use Google\ApiCore\Middleware\OperationsMiddleware;
use Google\ApiCore\Middleware\OptionsFilterMiddleware;
use Google\ApiCore\Middleware\PagedMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\ApiCore\Options\CallOptions;
use Google\ApiCore\Options\ClientOptions;
use Google\ApiCore\Transport\TransportInterface;
use Google\Auth\FetchAuthTokenInterface;
use Google\LongRunning\Operation;
use Google\Protobuf\Internal\Message;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Common functions used to work with various clients.
 *
 * @internal
 */
class ApiCallHandler
{
    /** @var array<callable> $middlewareCallables */
    private array $middlewareCallables = [];
    private array $transportCallMethods = [
        Call::UNARY_CALL => 'startUnaryCall',
        Call::BIDI_STREAMING_CALL => 'startBidiStreamingCall',
        Call::CLIENT_STREAMING_CALL => 'startClientStreamingCall',
        Call::SERVER_STREAMING_CALL => 'startServerStreamingCall',
    ];

    public function __construct(
        private ServiceDescriptor $descriptors,
        private CredentialsWrapper $credentialsWrapper,
        private TransportInterface $transport,
        private array $retrySettings = [],
        private array $agentHeader = [],
        private ?string $audience = null,
        private ?ServiceClientInterface $operationsClient = null,
    ) {

    }

    /**
     * Add a middleware to the call stack by providing a callable which will be
     * invoked at the start of each call, and will return an instance of
     * {@see MiddlewareInterface} when invoked.
     *
     * The callable must have the following method signature:
     *
     *     callable(MiddlewareInterface): MiddlewareInterface
     *
     * An implementation may look something like this:
     * ```
     * $client->addMiddleware(function (MiddlewareInterface $handler) {
     *     return new class ($handler) implements MiddlewareInterface {
     *         public function __construct(private MiddlewareInterface $handler) {
     *         }
     *
     *         public function __invoke(Call $call, array $options) {
     *             // modify call and options (pre-request)
     *             $response = ($this->handler)($call, $options);
     *             // modify the response (post-request)
     *             return $response;
     *         }
     *     };
     * });
     * ```
     *
     * @param callable $middlewareCallable A callable which returns an instance
     *                 of {@see MiddlewareInterface} when invoked with a
     *                 MiddlewareInterface instance as its first argument.
     * @return void
     */
    public function addMiddleware(callable $middlewareCallable): void
    {
        $this->middlewareCallables[] = $middlewareCallable;
    }

    /**
     * @param MethodDescriptor $method
     * @param Message $request
     * @param array $optionalArgs {
     *     Call Options
     *
     *     @type array $headers                     [optional] key-value array containing headers
     *     @type int $timeoutMillis                 [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions            [optional] transport-specific call options
     *     @type RetrySettings|array $retrySettings [optional] A retry settings override for the call.
     * }
     *
     * @experimental
     *
     * @return PromiseInterface
     */
    public function startAsyncCall(
        string $methodName,
        Message $request,
        array $optionalArgs = []
    ) {
        $method = $this->descriptors->getMethod($methodName);

        switch ($method->getCallType()) {
            case Call::PAGINATED_CALL:
                return $this->getPagedListResponseAsync($method, $optionalArgs, $request);
            case Call::SERVER_STREAMING_CALL:
            case Call::CLIENT_STREAMING_CALL:
            case Call::BIDI_STREAMING_CALL:
                throw new ValidationException(sprintf(
                    'Call type "%s" of requested method "%s" is not supported for async execution.',
                    $method->getCallType(),
                    $method->getName()
                ));

        }

        return $this->doApiCall($method, $request, $optionalArgs);
    }

    /**
     * @param MethodDescriptor $method
     * @param Message $request
     * @param array $optionalArgs {
     *     Call Options
     *
     *     @type array $headers [optional] key-value array containing headers
     *     @type int $timeoutMillis [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions [optional] transport-specific call options
     *     @type RetrySettings|array $retrySettings [optional] A retry settings
     *           override for the call.
     * }
     *
     * @experimental
     *
     * @return PromiseInterface|PagedListResponse|BidiStream|ClientStream|ServerStream
     */
    public function startApiCall(
        string $methodName,
        Message $request = null,
        array $optionalArgs = []
    ) {
        $method = $this->descriptors->getMethod($methodName);
        return $this->doApiCall($method, $request, $optionalArgs);
    }

    private function doApiCall(
        MethodDescriptor $method,
        Message $request = null,
        array $optionalArgs = []
    ) {
        // Prepare request-based headers, merge with user-provided headers,
        // which take precedence.
        $requestHeaders = $this->buildRequestParamsHeader((array) $method->getHeaderParams(), $request);
        $optionalArgs['headers'] = array_merge($requestHeaders, $optionalArgs['headers'] ?? []);

        // Handle call based on call type configured in the method descriptor config.
        if ($method->getCallType() == Call::LONGRUNNING_CALL) {
            if (is_null($this->operationsClient)) {
                throw new ValidationException(sprintf(
                    'Client missing required getOperationsClient for longrunning call "%s"',
                    $method->getName()
                ));
            }
            return $this->doOperationsCall(
                $method,
                $request,
                $optionalArgs,
                $this->operationsClient
            );
        }

        if ($method->getCallType() == Call::PAGINATED_CALL) {
            return $this->getPagedListResponse($method, $optionalArgs, $request);
        }

        $callStack = $this->createCallStack($method->getName(), $optionalArgs);

        $call = new Call(
            $method->getFullName(),
            $method->getResponseType(),
            $request,
            $method->getGrpcStreaming(),
            $method->getCallType()
        );

        return $callStack($call, $optionalArgs + array_filter([
            'audience' => $this->audience,
        ]));
    }

    /**
     * @param array $callConstructionOptions {
     *     Call Construction Options
     *
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     *
     * @return callable
     */
    private function createCallStack(string $methodName, array $optionalArgs)
    {
        $optionalArgs = $this->configureCallConstructionOptions(
            $methodName,
            $this->configureCallOptions($optionalArgs)
        );

        $quotaProject = $this->credentialsWrapper->getQuotaProject();
        $fixedHeaders = $this->agentHeader;
        if ($quotaProject) {
            $fixedHeaders += [
                'X-Goog-User-Project' => [$quotaProject]
            ];
        }
        $callStack = function (Call $call, array $options) {
            $startCallMethod = $this->transportCallMethods[$call->getCallType()];
            return $this->transport->$startCallMethod($call, $options);
        };
        $callStack = new CredentialsWrapperMiddleware($callStack, $this->credentialsWrapper);
        $callStack = new FixedHeaderMiddleware($callStack, $fixedHeaders, true);
        $callStack = new RetryMiddleware($callStack, $optionalArgs['retrySettings']);
        $callStack = new OptionsFilterMiddleware($callStack, [
            'headers',
            'timeoutMillis',
            'transportOptions',
            'metadataCallback',
            'audience',
            'metadataReturnType'
        ]);

        foreach (\array_reverse($this->middlewareCallables) as $fn) {
            /** @var MiddlewareInterface $callStack */
            $callStack = $fn($callStack);
        }

        return $callStack;
    }

    /**
     * @param string $methodName
     * @param array $optionalArgs {
     *     Optional arguments
     *
     *     @type RetrySettings|array $retrySettings [optional] A retry settings
     *           override for the call.
     * }
     *
     * @return array
     */
    private function configureCallConstructionOptions(string $methodName, array $optionalArgs)
    {
        $retrySettings = $this->retrySettings[$methodName] ?? RetrySettings::constructDefault();
        // Allow for retry settings to be changed at call time
        if (isset($optionalArgs['retrySettings'])) {
            if ($optionalArgs['retrySettings'] instanceof RetrySettings) {
                $retrySettings = $optionalArgs['retrySettings'];
            } else {
                $retrySettings = $retrySettings->with(
                    $optionalArgs['retrySettings']
                );
            }
        }
        return [
            'retrySettings' => $retrySettings,
        ];
    }

    /**
     * @return array
     */
    private function configureCallOptions(CallOptions|array $optionalArgs): array
    {
        if (is_array($optionalArgs)) {
            $optionalArgs = new CallOptions($optionalArgs);
        }
        // cast to CallOptions
        return $optionalArgs->toArray();
    }

    /**
     * @param string $methodName
     * @param array $optionalArgs {
     *     Call Options
     *
     *     @type array $headers [optional] key-value array containing headers
     *     @type int $timeoutMillis [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions [optional] transport-specific call options
     * }
     * @param Message $request
     * @param OperationsClient|object $operationsClient
     * @param string $interfaceName
     * @param string $operationClass If provided, will be used instead of the default
     *                               operation response class of {@see \Google\LongRunning\Operation}.
     *
     * @return PromiseInterface
     */
    private function doOperationsCall(
        MethodDescriptor $method,
        Message $request,
        array $optionalArgs,
        ServiceClientInterface $operationsClient,
    ) {
        $callStack = $this->createCallStack($method->getName(), $optionalArgs);
        $longRunning = $method->getLongRunning();

        // Call the methods supplied in "additionalArgumentMethods" on the request Message object
        // to build the "additionalOperationArguments" option for the operation response.
        if (isset($longRunning['additionalArgumentMethods'])) {
            $additionalArgs = [];
            foreach ($longRunning['additionalArgumentMethods'] as $additionalArgsMethodName) {
                $additionalArgs[] = $request->$additionalArgsMethodName();
            }
            $longRunning['additionalOperationArguments'] = $additionalArgs;
            unset($longRunning['additionalArgumentMethods']);
        }

        $callStack = new OperationsMiddleware($callStack, $operationsClient, $longRunning);

        $call = new Call(
            $method->getFullName(),
            $method->getResponseType(),
            $request,
            [],
            Call::UNARY_CALL
        );

        return $callStack($call, $optionalArgs + array_filter([
            'metadataReturnType' => $longRunning['metdataReturnType'] ?? null,
            'audience' => $this->audience,
        ]));
    }

    /**
     * @param string $methodName
     * @param array $optionalArgs
     * @param string $decodeType
     * @param Message $request
     * @param string $interfaceName
     *
     * @return PagedListResponse
     */
    private function getPagedListResponse(
        MethodDescriptor $method,
        array $optionalArgs,
        Message $request,
        string $interfaceName = null
    ) {
        return $this->getPagedListResponseAsync(
            $method,
            $optionalArgs,
            $request,
        )->wait();
    }

    /**
     * @param string $methodName
     * @param array $optionalArgs
     * @param string $decodeType
     * @param Message $request
     * @param string $interfaceName
     *
     * @return PromiseInterface
     */
    private function getPagedListResponseAsync(
        MethodDescriptor $method,
        array $optionalArgs,
        Message $request,
    ) {
        $callStack = $this->createCallStack($method->getName(), $optionalArgs);
        $descriptor = new PageStreamingDescriptor($method->getPageStreaming());
        $callStack = new PagedMiddleware($callStack, $descriptor);

        $call = new Call(
            $method->getFullName(),
            $method->getResponseType(),
            $request,
            [],
            Call::UNARY_CALL
        );

        return $callStack($call, $optionalArgs + array_filter([
            'audience' => $this->audience,
        ]));
    }

    /**
     * @param array $headerParams
     * @param Message|null $request
     *
     * @return array
     */
    private function buildRequestParamsHeader(array $headerParams, Message $request = null)
    {
        $headers = [];

        // No request message means no request-based headers.
        if (!$request) {
            return $headers;
        }

        foreach ($headerParams as $headerParam) {
            $msg = $request;
            $value = null;
            foreach ($headerParam['fieldAccessors'] as $accessor) {
                $value = $msg->$accessor();

                // In case the field in question is nested in another message,
                // skip the header param when the nested message field is unset.
                $msg = $value;
                if (is_null($msg)) {
                    break;
                }
            }

            $keyName = $headerParam['keyName'];

            // If there are value pattern matchers configured and the target
            // field was set, evaluate the matchers in the order that they were
            // annotated in with last one matching wins.
            $original = $value;
            $matchers = isset($headerParam['matchers']) && !is_null($value) ?
                $headerParam['matchers'] :
                [];
            foreach ($matchers as $matcher) {
                $matches = [];
                if (preg_match($matcher, $original, $matches)) {
                    $value = $matches[$keyName];
                }
            }

            // If there are no matches or the target field was unset, skip this
            // header param.
            if (!$value) {
                continue;
            }

            $headers[$keyName] = $value;
        }

        $requestParams = new RequestParamsHeaderDescriptor($headers);

        return $requestParams->getHeader();
    }

    public function getCredentialsWrapper(): CredentialsWrapper
    {
        return $this->credentialsWrapper;
    }
}
