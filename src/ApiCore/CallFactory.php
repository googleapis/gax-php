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
use Google\Cloud\Version;
use Google\Protobuf\Internal\Message;

class CallFactory
{
    private $transport;
    private $defaultRetrySettings;
    private $agentHeaderDescriptor;

    private $unaryMiddlewareStack = [
        AgentHeaderMiddleware::class,
        RetryMiddleware::class,
    ];

    private $streamingMiddlewareStack = [
        AgentHeaderMiddleware::class,
    ];

    /**
     * CallFactory constructor.
     *
     * @param TransportInterface $transport
     * @param RetrySettings[] $defaultRetrySettings
     * @param AgentHeaderDescriptor $agentHeaderDescriptor
     */
    public function __construct(TransportInterface $transport, array $defaultRetrySettings, AgentHeaderDescriptor $agentHeaderDescriptor)
    {
        $this->transport = $transport;
        $this->defaultRetrySettings = $defaultRetrySettings;
        $this->agentHeaderDescriptor = $agentHeaderDescriptor;
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
     * @return Promise
     */
    private function startUnaryCall(Call $call, array $options, array $callConstructionOptions)
    {
        $callStack = $this->createCallStack(
            'startUnaryCall',
            $this->unaryMiddlewareStack,
            $callConstructionOptions
        );
        return $callStack($call, $options);
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
     * @return BidiStream
     */
    private function startBidiStreamingCall(Call $call, array $options, array $callConstructionOptions)
    {
        $callStack = $this->createCallStack(
            'startBidiStreamingCall',
            $this->streamingMiddlewareStack,
            $callConstructionOptions
        );
        return $callStack($call, $options);
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
     * @return ClientStream
     */
    private function startClientStreamingCall(Call $call, array $options, array $callConstructionOptions)
    {
        $callStack = $this->createCallStack(
            'startClientStreamingCall',
            $this->streamingMiddlewareStack,
            $callConstructionOptions
        );
        return $callStack($call, $options);
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
     * @return ServerStream
     */
    private function startServerStreamingCall(Call $call, array $options, array $callConstructionOptions)
    {
        $callStack = $this->createCallStack(
            'startServerStreamingCall',
            $this->streamingMiddlewareStack,
            $callConstructionOptions
        );
        return $callStack($call, $options);
    }

    /**
     * @param string $methodCallMethod
     * @param string[] $middlewareStack
     * @param array $callConstructionOptions {
     *     Call Construction Options
     *
     *     @type RetrySettings $retrySettings [required] Retry settings for the call.
     *     @type AgentHeaderDescriptor $agentHeaderDescriptor [optional] AgentHeaderDescriptor for the call.
     * }
     * @return callable
     */
    private function createCallStack($methodCallMethod, array $middlewareStack, array $callConstructionOptions)
    {
        $callable =function (Call $call, array $options) use ($methodCallMethod) {
            return $this->transport->$methodCallMethod($call, $options);
        };
        foreach ($middlewareStack as $middleware) {
            $callable = new $middleware($callable, $callConstructionOptions);
        }
        return $callable;
    }

    /**
     * @param string $method
     * @param array $optionalArgs {
     *     Optional arguments
     *
     *     @type RetrySettings $retrySettings [optional] A retry settings override
     *           For the call.
     * }
     * @return array
     */
    private function configureCallConstructionOptions($method, array $optionalArgs)
    {
        $retrySettings = $this->defaultRetrySettings[$method];
        // Allow for retry settings to be changed at call time
        if (isset($optionalArgs['retrySettings'])) {
            $retrySettings = $retrySettings->with(
               $optionalArgs['retrySettings']
            );
        }
        return [
            'retrySettings' => $retrySettings,
            'agentHeaderDescriptor' => $this->agentHeaderDescriptor,
        ];
    }
}
