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
use Google\ApiCore\Middleware\AuthWrapperMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\ApiCore\Transport\TransportInterface;
use Google\Auth\FetchAuthTokenInterface;
use Google\LongRunning\Operation;
use Google\Protobuf\Internal\Message;
use GuzzleHttp\Promise\PromiseInterface;

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
    private $authWrapper;
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
     * Placeholder function - classes that use this trait are expected to implement their
     * own getClientDefaults method, which will be used via late static binding.
     * @return array
     */
    private static function getClientDefaults()
    {
        return [];
    }

    /**
     * Configures the GAPIC client based on an array of options.
     *
     * @param array $options {
     *     An array of required and optional arguments.
     *
     * @type string $serviceAddress
     *           The address of the API remote host, for example "example.googleapis.com. May also
     *           include the port, for example "example.googleapis.com:443"
     * @type bool $disableRetries Determines whether or not retries defined
     *           by the client configuration should be disabled. Defaults to `false`.
     * @type string|array $clientConfig
     *           Client method configuration, including retry settings. This option can be either a
     *           path to a JSON file, or a PHP array containing the decoded JSON data.
     *           By default this settings points to the default client config file, which is provided
     *           in the resources folder.
     * @type string|array $auth
     *           The credentials to be used by the client to authorize API calls. This option
     *           accepts either a path to a credentials file, or a decoded credentials file as a
     *           PHP array.
     *           *Advanced usage*: In addition, this option can also accept a pre-constructed
     *           \Google\Auth\FetchAuthTokenInterface object or \Google\ApiCore\AuthWrapper
     *           object. Note that when one of these objects are provided, any settings in
     *           $authConfig will be ignored.
     * @type array $authConfig
     *           Options used to configure auth, including auth token caching, for the client. For
     *           a full list of supporting configuration options, see \Google\ApiCore\Auth::build.
     * @type string $transport The transport used for executing network
     *           requests. May be either the string `rest` or `grpc`. Defaults to `grpc` if gRPC
     *           support is detected on the system.
     *           *Advanced usage*: Additionally, it is possible to pass in an already instantiated
     *           TransportInterface object. Note that when this objects is provided, any settings in
     *           $transportConfig, and any $serviceAddress setting, will be ignored.
     * @type array $transportConfig
     *           Configuration options that will be used to construct the transport. Options for
     *           each supported transport type should be passed in a key for that transport. For
     *           example:
     *           $transportConfig = [
     *               'grpc' => [...],
     *               'rest' => [...]
     *           ];
     *           See the GapicClientTrait::buildGrpcTransport and GapicClientTrait::buildRestTransport
     *           methods for the supported options.
     * @type string $versionFile
     *           The path to a file which contains the current version of the client.
     * @type string $descriptorsConfigPath
     *           The path to a descriptor configuration file.
     * @type string $serviceName
     *           The name of the service.
     * @type string $libName
     *           The name of the client application.
     * @type string $libVersion
     *           The version of the client application.
     * @type string $gapicVersion
     *           The code generator version of the GAPIC library.
     * }
     * @throws ValidationException
     * @throws \Exception
     */
    protected function setClientOptions(array $options)
    {
        $options += self::getClientDefaults();

        $this->setServiceNameAndDescriptors($options);
        $this->setRetrySettings($options);
        $this->setAgentHeaderDescriptor($options);
        $this->setAuthWrapper($options);
        $this->setTransport($options);
    }

    /**
     * @param array $options
     */
    private function setServiceNameAndDescriptors($options)
    {
        $this->validateNotNull($options, [
            'serviceName',
            'descriptorsConfigPath',
        ]);

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
        $this->validateNotNull($options, [
            'serviceName',
            'clientConfig',
            'disableRetries',
        ]);

        $clientConfig = $options['clientConfig'];
        if (is_string($clientConfig)) {
            $clientConfig = json_decode(file_get_contents($clientConfig), true);
        }

        $retrySettings = RetrySettings::load(
            $options['serviceName'],
            $clientConfig,
            null
        );
        if ($options['disableRetries']) {
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
        ]);
    }

    /**
     * @param $options
     * @throws ValidationException
     * @throws \Exception
     */
    private function setAuthWrapper($options)
    {
        $this->validate($options, [
            'auth',
            'authConfig',
        ]);
        $auth = $options['auth'];
        $authConfig = $options['authConfig'] ?: [];

        if (is_null($auth)) {
            $this->authWrapper = AuthWrapper::from($authConfig);
        } elseif (is_string($auth) || is_array($auth)) {
            $this->authWrapper = AuthWrapper::fromKeyFile($auth, $authConfig);
        } elseif ($auth instanceof FetchAuthTokenInterface) {
            $this->authWrapper = new AuthWrapper($auth, $options['authHttpHandler']);
        } elseif ($auth instanceof AuthWrapper) {
            $this->authWrapper = $auth;
        } else {
            throw new ValidationException(
                "Unexpected value in \$auth option, got: " .
                print_r($auth, true)
            );
        }
    }

    /**
     * @param array $options
     * @throws ValidationException
     */
    private function setTransport($options)
    {
        $this->validate($options, [
            'serviceAddress',
            'transport',
            'transportConfig',
        ]);
        $serviceAddress = $options['serviceAddress'];
        $transport = $options['transport'] ?: self::defaultTransport();
        $transportConfig = $options['transportConfig'] ?: [];

        if ($transport instanceof TransportInterface) {
            $this->transport = $transport;
        } elseif (is_string($transport)) {
            $configForSpecifiedTransport = isset($transportConfig[$transport])
                ? $transportConfig[$transport]
                : [];
            switch ($transport) {
                case 'grpc':
                    $this->transport = TransportFactory::buildGrpcTransport($serviceAddress, $configForSpecifiedTransport);
                    break;
                case 'rest':
                    $this->transport = TransportFactory::buildRestTransport($serviceAddress, $configForSpecifiedTransport);
                    break;
                default:
                    throw new ValidationException(
                        "Unexpected 'transport' option: $transport. " .
                        "Supported values: ['grpc', 'rest']"
                    );
            }
        } else {
            throw new ValidationException(
                "'transport' must be either a string " .
                "or an instance of TransportInterface, instead got:" .
                print_r($transport, true)
            );
        }
    }

    /**
     * @return bool
     */
    private static function getGrpcDependencyStatus()
    {
        return extension_loaded('grpc');
    }

    /**
     * @return string
     */
    private static function defaultTransport()
    {
        return self::getGrpcDependencyStatus()
            ? 'grpc'
            : 'rest';
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
                new AuthWrapperMiddleware(
                    function (Call $call, array $options) {
                        $startCallMethod = $this->transportCallMethods[$call->getCallType()];
                        return $this->transport->$startCallMethod($call, $options);
                    },
                    $this->authWrapper
                ),
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
