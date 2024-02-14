<?php
/*
 * Copyright 2018 Google LLC
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
trait ClientTrait
{
    use ClientOptionsTrait;
    use OperationsSupportTrait;
    use TransportSupportTrait;
    use ValidationTrait {
        ValidationTrait::validate as traitValidate;
    }
    use GrpcSupportTrait;

    private CallHandler $callHandler;

    /**
     * Configures the GAPIC client based on an array of options.
     *
     * @param array $options {
     *     An array of required and optional arguments.
     *
     *     @type string $apiEndpoint
     *           The address of the API remote host, for example "example.googleapis.com. May also
     *           include the port, for example "example.googleapis.com:443"
     *     @type bool $disableRetries
     *           Determines whether or not retries defined by the client configuration should be
     *           disabled. Defaults to `false`.
     *     @type string|array $clientConfig
     *           Client method configuration, including retry settings. This option can be either a
     *           path to a JSON file, or a PHP array containing the decoded JSON data.
     *           By default this settings points to the default client config file, which is provided
     *           in the resources folder.
     *     @type string|array|FetchAuthTokenInterface|CredentialsWrapper $credentials
     *           The credentials to be used by the client to authorize API calls. This option
     *           accepts either a path to a credentials file, or a decoded credentials file as a
     *           PHP array.
     *           *Advanced usage*: In addition, this option can also accept a pre-constructed
     *           \Google\Auth\FetchAuthTokenInterface object or \Google\ApiCore\CredentialsWrapper
     *           object. Note that when one of these objects are provided, any settings in
     *           $authConfig will be ignored.
     *     @type array $credentialsConfig
     *           Options used to configure credentials, including auth token caching, for the client.
     *           For a full list of supporting configuration options, see
     *           \Google\ApiCore\CredentialsWrapper::build.
     *     @type string|TransportInterface $transport
     *           The transport used for executing network requests. May be either the string `rest`,
     *           `grpc`, or 'grpc-fallback'. Defaults to `grpc` if gRPC support is detected on the system.
     *           *Advanced usage*: Additionally, it is possible to pass in an already instantiated
     *           TransportInterface object. Note that when this objects is provided, any settings in
     *           $transportConfig, and any `$apiEndpoint` setting, will be ignored.
     *     @type array $transportConfig
     *           Configuration options that will be used to construct the transport. Options for
     *           each supported transport type should be passed in a key for that transport. For
     *           example:
     *           $transportConfig = [
     *               'grpc' => [...],
     *               'rest' => [...],
     *               'grpc-fallback' => [...],
     *           ];
     *           See the GrpcTransport::build and RestTransport::build
     *           methods for the supported options.
     *     @type string $versionFile
     *           The path to a file which contains the current version of the client.
     *     @type string $descriptorsConfigPath
     *           The path to a descriptor configuration file.
     *     @type string $serviceName
     *           The name of the service.
     *     @type string $libName
     *           The name of the client application.
     *     @type string $libVersion
     *           The version of the client application.
     *     @type string $gapicVersion
     *           The code generator version of the GAPIC library.
     *     @type callable $clientCertSource
     *           A callable which returns the client cert as a string.
     * }
     * @throws ValidationException
     */
    private function setClientOptions(ClientOptions $options)
    {
        $options->validateNotNull('apiEndpoint');
        $options->validateNotNull('serviceName');
        $options->validateNotNull('descriptorsConfigPath');
        $options->validateNotNull('clientConfig');
        $options->validateNotNull('disableRetries');
        $options->validateNotNull('credentialsConfig');
        $options->validateNotNull('transportConfig');

        $serviceName = $options['serviceName'];
        $retrySettings = RetrySettings::load(
            $serviceName,
            $options['clientConfig'],
            $options['disableRetries']
        );

        $transportOption = $options['transport'] ?: self::defaultTransport();
        $transport = $transportOption instanceof TransportInterface
            ? $transportOption
            : $this->createTransport(
                $options['apiEndpoint'],
                $transportOption,
                $options['transportConfig'],
                $options['clientCertSource']
            );

        $headerInfo = [
            'libName' => $options['libName'],
            'libVersion' => $options['libVersion'],
            'gapicVersion' => $options['gapicVersion'],
        ];

        // Edge case: If the client has the gRPC extension installed, but is
        // a REST-only library, then the grpcVersion header should not be set.
        if ($transport instanceof GrpcTransport) {
            $headerInfo['grpcVersion'] = phpversion('grpc');
        } elseif ($transport instanceof RestTransport
            || $transport instanceof GrpcFallbackTransport) {
            $headerInfo['restVersion'] = Version::getApiCoreVersion();
        }

        // Set "client_library_name" depending on client library surface being used
        $userAgentHeader = sprintf(
            'gcloud-php-new/%s',
            $options['gapicVersion']
        );
        $agentHeader = AgentHeader::buildAgentHeader($headerInfo);
        $agentHeader['User-Agent'] = [$userAgentHeader];

        $credentialsWrapper = $this->createCredentialsWrapper(
            $options['credentials'],
            $options['credentialsConfig'],
            $options['universeDomain']
        );

        self::validateFileExists($options['descriptorsConfigPath']);
        $descriptors = require($options['descriptorsConfigPath']);
        $serviceDescriptor = new ServiceDescriptor(
            $serviceName,
            $descriptors['interfaces'][$serviceName]
        );

        $this->callHandler = new CallHandler(
            $serviceDescriptor,
            $credentialsWrapper,
            $transport,
            $retrySettings,
            $agentHeader,
            defined('self::SERVICE_ADDRESS') ? 'https://' . self::SERVICE_ADDRESS . '/' : null,
            $this->operationsClient ?? null,
        );
    }

    /**
     * @param string $methodName
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
    private function startApiCall(
        string $methodName,
        Message $request = null,
        array $optionalArgs = []
    ) {
        return $this->callHandler->startApiCall(
            $methodName,
            $request,
            $optionalArgs,
        );
    }

    /**
     * @param string $methodName
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
    private function startAsyncCall(
        string $methodName,
        Message $request,
        array $optionalArgs = []
    ) {
        // Convert method name to the UpperCamelCase of RPC names from lowerCamelCase of GAPIC method names
        // in order to find the method in the descriptor config.
        $methodName = ucfirst($methodName);

        return $this->callHandler->startAsyncCall($method, $request, $optionalArgs);
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
        $this->callHandler->addMiddleware($middlewareCallable);
    }
}
