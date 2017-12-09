<?php
/*
 * Copyright 2016, Google Inc.
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

namespace Google\ApiCore\Tests\Mocks;

use Google\ApiCore\ApiException;
use Google\ApiCore\ApiTransportInterface;
use Google\ApiCore\ApiTransportTrait;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\Rpc\Code;
use GuzzleHttp\Promise\Promise;

class MockTransport implements ApiTransportInterface
{
    use MockStubTrait;
    use ApiTransportTrait;

    private $agentHeaderDescriptor;
    private $streamingDescriptor;

    public function setAgentHeaderDescriptor($agentHeaderDescriptor)
    {
        $this->agentHeaderDescriptor = $agentHeaderDescriptor;
    }

    private function getCallable(CallSettings $settings)
    {
        $callable = function (Call $call, CallSettings $settings) {
            $call = call_user_func([$this, $call->getMethod()], $call, $settings);
            $promise = new Promise(
                function () use ($call, &$promise) {
                    list($response, $status) = $call->wait();

                    if ($status->code == Code::OK) {
                        $promise->resolve($response);
                    } else {
                        throw ApiException::createFromStdClass($status);
                    }
                },
                [$call, 'cancel']
            );

            return $promise;
        };

        return $this->createCallStack($callable, $settings);
    }

    public function startStreamingCall(Call $call, CallSettings $callSettings, array $streamingDescriptor)
    {
        $handler = [$this, $method];
        $callable = function () use ($handler) {
            return call_user_func_array($handler, func_get_args());
        };
        $this->streamingDescriptor = $streamingDescriptor;
        return $this->createCallStack($callable, $settings, $options);
    }

    public function __call($name, $arguments)
    {
        if ($this->streamingDescriptor) {
            if (array_key_exists('headers', $optionalArgs)) {
                $metadata = $optionalArgs['headers'];
            }

            switch ($this->descriptor['streamingType']) {
                case 'BidiStreaming':
                    $newArgs = [$name, $this->deserialize, $metadata, $optionalArgs];
                    $response = call_user_func_array(array($this, '_bidiRequest'), $newArgs);
                    return new BidiStream($response, $this->descriptor);

                case 'ClientStreaming':
                    $newArgs = [$name, $this->deserialize, $metadata, $optionalArgs];
                    $response = call_user_func_array(array($this, '_clientStreamRequest'), $newArgs);
                    return new ClientStream($response, $this->descriptor);

                case 'ServerStreaming':
                    $newArgs = [$name, $request, $this->deserialize, $metadata, $optionalArgs];
                    $response = call_user_func_array(array($this, '_serverStreamRequest'), $newArgs);
                    return new ServerStream($response, $this->descriptor);

                default:
                    throw new \Exception('Invalid streaming type');
            }
        }

        $call = $arguments[0];
        $settings = $arguments[1];
        $decode = $call->getDecodeType() ? [$call->getDecodeType(), 'decode'] : null;
        return $this->_simpleRequest(
            '/' . $call->getMethod(),
            $call->getMessage(),
            $decode,
            $settings->getUserHeaders() ?: [],
            $this->getOptions($settings)
        );
    }

    public function methodThatSleeps(Call $call, CallSettings $settings)
    {
        $metadata = [];
        $options = $this->getOptions($settings);
        $this->receivedFuncCalls[] = new ReceivedRequest(
            'methodThatSleeps',
            $call->getMessage(),
            $this->deserialize,
            $metadata,
            $options
        );
        $timeout = isset($options['timeout']) ? $options['timeout'] : null;
        $call = new MockDeadlineExceededUnaryCall($timeout * 1000);
        $this->callObjects[] = $call;
        return $call;
    }

    private function getOptions(CallSettings $settings)
    {
        return ['timeout' => $settings->getTimeoutMillis()];
    }
}
