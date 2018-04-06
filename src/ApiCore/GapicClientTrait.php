<?php
/*
 * Copyright 2018, Google Inc.
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

use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\Middleware\AgentHeaderMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\ApiCore\Transport\TransportInterface;
use Google\LongRunning\Operation;
use Google\Protobuf\Internal\Message;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Common functions used to work with various clients.
 */
trait GapicClientTrait
{
    use ArrayTrait;
    use ValidationTrait;

    protected $transport;
    private static $gapicVersion;
    private $retrySettings;
    private $serviceName;
    private $agentHeaderDescriptor;
    private $descriptors;
    private $transportCallMethods = [
        Call::UNARY_CALL => 'startUnaryCall',
        Call::BIDI_STREAMING_CALL => 'startBidiStreamingCall',
        Call::CLIENT_STREAMING_CALL => 'startClientStreamingCall',
        Call::SERVER_STREAMING_CALL => 'startServerStreamingCall',
    ];

    /**
     * Initiates an orderly shutdown in which preexisting calls continue but new
     * calls are immediately cancelled.
     *
     * @experimental
     */
    public function close()
    {
        $this->transport->close();
    }

    private static function getGapicVersion(array $options)
    {
        if (!self::$gapicVersion) {
            if (isset($options['versionFile']) && file_exists($options['versionFile'])) {
                self::$gapicVersion = trim(file_get_contents(
                    $options['versionFile']
                ));
            } elseif (isset($options['libVersion'])) {
                self::$gapicVersion = $options['libVersion'];
            }
        }

        return self::$gapicVersion;
    }

    /**
     * Configures the GAPIC client based on an array of options.
     *
     * @param array $options {
     *     An array of required and optional arguments.
     *
     *     @type string $versionFile
     *           The path to a file which contains the current version of the
     *           client.
     *     @type string $descriptorsConfigPath
     *           The path to a descriptor configuration file.
     *     @type string $serviceName
     *           The name of the service.
     *     @type string $serviceAddress
     *           The address of the API remote host. Formatted as <endpoint>:<port>, for example:
     *           "example.googleapis.com:443"
     *     @type array $keyFile
     *           The contents of the service account credentials .json file retrieved from the
     *           Google Developer's Console.
     *           Ex: `json_decode(file_get_contents($path), true)`.
     *     @type string $keyFilePath
     *           The full path to your service account credentials .json file retrieved from the
     *           Google Developers Console.
     *     @type array $scopes
     *           A string array of scopes to use when acquiring credentials.
     *     @type string $clientConfig
     *           Array containing client method configuration, including retry settings. If this argument
     *           is specified, the value of the $clientConfigPath option will be ignored.
     *     @type string $clientConfigPath
     *           Path to a JSON file containing client method configuration, including retry settings.
     *           Specify this setting to specify the retry behavior of all methods on the client.
     *           If the $clientConfig option is specified, this will be ignored.
     *     @type bool $disableRetries
     *           Determines whether or not retries defined by the client configuration should be
     *           disabled. Defaults to `false`.
     *     @type string|TransportInterface $transport
     *           The transport used for executing network requests. May be either
     *           the string `rest` or `grpc`. Additionally, it is possible
     *           to pass in an already instantiated transport. Defaults to `grpc`
     *           if gRPC support is detected on the system.
     *     @type CacheItemPoolInterface $authCache A cache for storing access
     *           tokens. Defaults to a simple in memory implementation.
     *     @type string $libName
     *           The name of the client application.
     *     @type string $libVersion
     *           The version of the client application.
     *     @type string $gapicVersion
     *           The code generator version of the GAPIC library.
     * }
     * @throws ValidationException
     */
    protected function setClientOptions(array $options)
    {
        $this->validateOptions($options);

        $this->setServiceNameAndDescriptors($options);
        $this->setRetrySettings($options);
        $this->setAgentHeaderDescriptor($options);
        $this->setTransport($options);
    }

    /**
     * @param array $options
     * @throws ValidationException
     */
    private function validateOptions(array $options)
    {
        $this->validateNotNull($options, [
            'serviceName',
            'serviceAddress',
            'descriptorsConfigPath',
            'clientConfigPath',
            'scopes',
        ]);

        if (isset($options['transport'])) {
            $transport = $options['transport'];
            if (!($transport instanceof TransportInterface) && !is_string($transport)) {
                throw new ValidationException(
                    "Options 'transport' must be either a string " .
                    "or an instance of TransportInterface, instead got:" .
                    print_r($transport, true)
                );
            }
        }
    }

    /**
     * @param array $options
     */
    private function setServiceNameAndDescriptors($options)
    {
        $serviceName = $options['serviceName'];
        $descriptors = require($options['descriptorsConfigPath']);

        $this->serviceName = $serviceName;
        $this->descriptors = $descriptors['interfaces'][$serviceName];
    }

    /**
     * @param array $options
     * @throws ValidationException
     */
    private function setRetrySettings($options)
    {
        $clientConfig = isset($options['clientConfig'])
            ? $options['clientConfig']
            : json_decode(file_get_contents($options['clientConfigPath']), true);

        $retrySettings = RetrySettings::load(
            $options['serviceName'],
            $clientConfig,
            null
        );
        if (isset($options['disableRetries']) && $options['disableRetries']) {
            $updatedRetrySettings = [];
            foreach ($retrySettings as $method => $retrySettingsItem) {
                $updatedRetrySettings[$method] = $retrySettingsItem->with([
                    'retriesEnabled' => false
                ]);
            }
            $retrySettings = $updatedRetrySettings;
        }

        $this->retrySettings = $retrySettings;
    }

    /**
     * @param array $options
     */
    private function setAgentHeaderDescriptor($options)
    {
        $gapicVersion = isset($options['gapicVersion'])
            ? $options['gapicVersion']
            : self::getGapicVersion($options);
        $this->agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => $this->pluck('libName', $options, false),
            'libVersion' => $this->pluck('libVersion', $options, false),
            'gapicVersion' => $gapicVersion,
            'phpVersion' => $this->pluck('phpVersion', $options, false),
            'apiCoreVersion' => $this->pluck('apiCoreVersion', $options, false),
            'grpcVersion' => $this->pluck('grpcVersion', $options, false),
        ]);
    }

    /**
     * @param array $options
     * @throws ValidationException
     */
    private function setTransport($options)
    {
        if (isset($options['transport']) && $options['transport'] instanceof TransportInterface) {
            $this->transport = $options['transport'];
            return;
        }

        $authWrapperOptions = $this->subsetArray([
            'keyFile',
            'keyFilePath',
            'authCache',
        ], $options);
        $authWrapper = AuthWrapper::createWithScopes($options['scopes'],$authWrapperOptions);

        $transportOptions = $this->subsetArray([
            'transport',
            'restClientConfigPath',
        ], $options);
        $this->transport = TransportFactory::build($options['serviceAddress'], $authWrapper, $transportOptions);
    }

    /**
     * @param string $methodName
     * @param string $decodeType
     * @param array $optionalArgs {
     *     Call Options
     *
     *     @type array $headers [optional] key-value array containing headers
     *     @type int $timeoutMillis [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions [optional] transport-specific call options
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     * @param Message $request
     * @param int $callType
     * @param string $interfaceName
     *
     * @return PromiseInterface|BidiStream|ClientStream|ServerStream
     */
    protected function startCall(
        $methodName,
        $decodeType,
        array $optionalArgs = [],
        Message $request = null,
        $callType = Call::UNARY_CALL,
        $interfaceName = null
    ) {
        $callStack = $this->createCallStack(
            $this->configureCallConstructionOptions($methodName, $optionalArgs)
        );

        $descriptor = isset($this->descriptors[$methodName]['grpcStreaming'])
            ? $this->descriptors[$methodName]['grpcStreaming']
            : null;

        $call = new Call(
            $this->buildMethod($interfaceName, $methodName),
            $decodeType,
            $request,
            $descriptor,
            $callType
        );
        return $callStack(
            $call,
            $this->configureCallOptions($optionalArgs)
        );
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
    protected function createCallStack(array $callConstructionOptions)
    {
        return new RetryMiddleware(
            new AgentHeaderMiddleware(
                function (Call $call, array $options) {
                    $startCallMethod = $this->transportCallMethods[$call->getCallType()];
                    return $this->transport->$startCallMethod($call, $options);
                },
                $this->agentHeaderDescriptor
            ),
            $callConstructionOptions['retrySettings']
        );
    }

    /**
     * @param array $optionalArgs {
     *     Optional arguments
     *
     *     @type array $headers [optional] key-value array containing headers
     *     @type int $timeoutMillis [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions [optional] transport-specific call options
     * }
     *
     * @return array
     */
    protected function configureCallOptions(array $optionalArgs)
    {
        return $this->pluckArray([
            'headers',
            'timeoutMillis',
            'transportOptions',
        ], $optionalArgs);
    }

    /**
     * @param string $methodName
     * @param array $optionalArgs {
     *     Optional arguments
     *
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     *
     * @return array
     */
    protected function configureCallConstructionOptions($methodName, array $optionalArgs)
    {
        $retrySettings = $this->retrySettings[$methodName];
        // Allow for retry settings to be changed at call time
        if (isset($optionalArgs['retrySettings'])) {
            $retrySettings = $retrySettings->with(
                $optionalArgs['retrySettings']
            );
        }
        return [
            'retrySettings' => $retrySettings,
        ];
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
     * @param OperationsClient $client
     * @param string $interfaceName
     *
     * @return PromiseInterface
     */
    protected function startOperationsCall(
        $methodName,
        array $optionalArgs,
        Message $request,
        OperationsClient $client,
        $interfaceName = null
    ) {
        $descriptor = $this->descriptors[$methodName]['longRunning'];
        return $this->startCall(
            $methodName,
            Operation::class,
            $optionalArgs,
            $request,
            Call::UNARY_CALL,
            $interfaceName
        )->then(function (Message $response) use ($client, $descriptor) {
            $options = $descriptor + [
                'lastProtoResponse' => $response
            ];

            return new OperationResponse($response->getName(), $client, $options);
        });
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
    protected function getPagedListResponse(
        $methodName,
        array $optionalArgs,
        $decodeType,
        Message $request,
        $interfaceName = null
    ) {
        $call = new Call(
            $this->buildMethod($interfaceName, $methodName),
            $decodeType,
            $request
        );
        return new PagedListResponse(
            $call,
            $this->configureCallOptions($optionalArgs),
            $this->createCallStack(
                $this->configureCallConstructionOptions($methodName, $optionalArgs)
            ),
            new PageStreamingDescriptor(
                $this->descriptors[$methodName]['pageStreaming']
            )
        );
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     *
     * @return string
     */
    protected function buildMethod($interfaceName, $methodName)
    {
        return sprintf(
            '%s/%s',
            $interfaceName ?: $this->serviceName,
            $methodName
        );
    }
}
