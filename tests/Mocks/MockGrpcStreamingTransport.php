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

namespace Google\GAX\UnitTests\Mocks;

use Google\GAX\ApiTransportInterface;
use Google\GAX\CallSettings;
use Google\GAX\CallStackTrait;
use Google\GAX\Grpc\GrpcBidiStream;
use Google\GAX\Grpc\GrpcClientStream;
use Google\GAX\Grpc\GrpcServerStream;

class MockGrpcStreamingTransport implements ApiTransportInterface
{
    use CallStackTrait;

    private $stub;
    private $descriptor;

    public function __construct($stub = null, $descriptor = null)
    {
        $this->stub = $stub;
        $this->descriptor = $descriptor;
    }

    /**
     * Creates a sequence such that the responses are returned in order.
     * @param mixed[] $sequence
     * @param $finalStatus
     * @param callable $deserialize
     * @return MockBidiStreamingStub
     */
    public static function create($stub, $descriptor = null)
    {
        return new self($stub, $descriptor);
    }

    /**
     * Creates an API request
     * @return callable
     */
    public function createApiCall($method, CallSettings $settings, $options = [])
    {
        $handler = [$this, $method];
        $callable = function () use ($handler) {
            return call_user_func_array($handler, func_get_args());
        };
        return $this->createCallStack($callable, $settings, $options);
    }

    public function __call($name, $arguments)
    {
        $metadata = [];
        $options = [];
        list($request, $optionalArgs) = $arguments;

        if (array_key_exists('headers', $optionalArgs)) {
            $metadata = $optionalArgs['headers'];
        }

        $newArgs = [$request, $metadata, $optionalArgs];
        $response = call_user_func_array([$this->stub, $name], $newArgs);

        switch ($this->descriptor['grpcStreamingType']) {
            case 'BidiStreaming':
                return new GrpcBidiStream($response, $this->descriptor);

            case 'ClientStreaming':
                return new GrpcClientStream($response, $this->descriptor);

            case 'ServerStreaming':
                return new GrpcServerStream($response, $this->descriptor);

            default:
                throw new \Exception('Invalid streaming type');
        }
    }
}
