<?php
/*
 * Copyright 2017, Google Inc.
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
use Google\Protobuf\Internal\Message;

/**
 * Common functions used to work with various clients.
 */
trait GapicClientTrait
{
    use ArrayTrait;
    use ValidationTrait;

    private static $gapicVersion;
    private $retrySettings;
    private $serviceName;
    private $agentHeaderDescriptor;
    private $descriptors;
    private $transport;
    private $transportCallMethods = [
        Call::UNARY_CALL => 'startUnaryCall',
        Call::BIDI_STREAMING_CALL => 'startBidiStreamingCall',
        Call::CLIENT_STREAMING_CALL => 'startClientStreamingCall',
        Call::SERVER_STREAMING_CALL => 'startServerStreamingCall',
    ];

    private static function getGapicVersion(array $options)
    {
        if (!self::$gapicVersion) {
            if (isset($options['versionFile'])) {
                if (file_exists($versionFile)) {
                    self::$gapicVersion = trim(file_get_contents(
                        $options['versionFile']
                    ));
                }
            } elseif (isset($options['libVersion'])) {
                $options['gapicVersion'] = $options['libVersion'];
            }
        }

        return self::$gapicVersion;
    }

    /**
     * Configures the GAPIC client based on an array of options.
     *
     * @param array $options {
     *     Required. An array of required and optional arguments.
     *
     *     @type string $libName
     *           Optional. The name of the client application.
     *     @type string $libVersion
     *           Optional. The version of the client application.
     *     @type string $gapicVersion
     *           Optional. The code generator version of the GAPIC library.
     * }
     */
    private function setClientOptions(array $options)
    {
        $this->validateNotNull($options, [
            'serviceName',
            'descriptorsConfigPath',
            'clientConfigPath'
        ]);
        $transport = isset($options['transport'])
            ? $options['transport']
            : null;
        $clientConfig = json_decode(
            file_get_contents($options['clientConfigPath']),
            true
        );
        $this->serviceName = $options['serviceName'];
        $this->retrySettings = RetrySettings::load(
            $this->serviceName,
            $clientConfig,
            $this->pluck('retryingOverride', $options, false)
        );
        if (!isset($options['gapicVersion'])) {
            $options['gapicVersion'] = self::getGapicVersion($options);
        }
        $this->agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => $this->pluck('libName', $options, false),
            'libVersion' => $this->pluck('libVersion', $options, false),
            'gapicVersion' => $options['gapicVersion'],
        ]);
        $descriptors = require($options['descriptorsConfigPath']);
        $this->descriptors = $descriptors['interfaces'][$this->serviceName];
        $this->transport = $transport instanceof TransportInterface
            ? $transport
            : TransportFactory::build($options);
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
     * @param array $descriptor
     * @param int $callType
     *
     * @return Promise
     */
    private function startCall(
        $methodName,
        $decodeType,
        array $optionalArgs = [],
        Message $request = null,
        array $descriptor = null,
        $callType = Call::UNARY_CALL
    ) {
        $callStack = $this->createCallStack(
            $this->configureCallConstructionOptions($methodName, $optionalArgs)
        );
        $call = new Call(
            $this->serviceName . '/' . $methodName,
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
    private function createCallStack(array $callConstructionOptions)
    {
        return new RetryMiddleware(
            new AgentHeaderMiddleware(
                function (Call $call, array $options) {
                    $startCallMethod = $this->transportCallMethods[$call->getType()];
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
    private function configureCallOptions(array $optionalArgs)
    {
        return $this->pluckArray([
            'headers',
            'timeoutMillis',
            'transportOptions',
        ], $optionalArgs);
    }

    /**
     * @param string $method
     * @param array $optionalArgs {
     *     Optional arguments
     *
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     *
     * @return array
     */
    private function configureCallConstructionOptions($methodName, array $optionalArgs)
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
     * @param Call $call
     * @param array $options {
     *     Call Options
     *
     *     @type array $headers [optional] key-value array containing headers
     *     @type int $timeoutMillis [optional] the timeout in milliseconds for the call
     *     @type array $transportOptions [optional] transport-specific call options
     * }
     * @param array $callConstructionOptions {
     *     Call Construction Options
     *
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     * @param OperationsClient $client
     * @param array $descriptor
     *
     * @return PromiseInterface
     */
    private function startOperationsCall(
        $methodName,
        array $optionalArgs,
        Message $request,
        OperationsClient $client
    ) {
        $descriptor = $this->descriptors[$methodName]['longRunning'];
        return $this->startCall(
            $methodName,
            Operation::class,
            $optionalArgs,
            $request
        )->then(function (Message $response) use ($client, $descriptor) {
            $options = $descriptor + [
                'lastProtoResponse' => $response
            ];

            return new OperationResponse($response->getName(), $client, $options);
        });
    }

    /**
     * @param Call $call
     * @param array $options
     * @param array $descriptor
     *
     * @return PagedListResponse
     */
    private function getPagedListResponse($methodName, array $optionalArgs, $decodeType, Message $request)
    {
        $call = new Call(
            $this->serviceName . '/' . $methodName,
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
     * Initiates an orderly shutdown in which preexisting calls continue but new
     * calls are immediately cancelled.
     *
     * @experimental
     */
    public function close()
    {
        $this->transport->close();
    }
}
