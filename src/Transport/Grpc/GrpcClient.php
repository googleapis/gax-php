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

namespace Google\ApiCore\Transport\Grpc;

use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\BidiStream;
use Google\ApiCore\Call;
use Google\ApiCore\ClientStream;
use Google\ApiCore\GrpcSupportTrait;
use Google\ApiCore\ServerStream;
use Google\ApiCore\ServiceAddressTrait;
use Google\ApiCore\Transport\Grpc\GrpcClient;
use Google\ApiCore\Transport\Grpc\ServerStreamingCallWrapper;
use Google\ApiCore\Transport\Grpc\UnaryInterceptorInterface;
use Google\ApiCore\ValidationException;
use Google\ApiCore\ValidationTrait;
use Google\Rpc\Code;
use Grpc\BaseStub;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use GuzzleHttp\Promise\Promise;

/**
 * A gRPC based transport implementation.
 */
class GrpcClient extends BaseStub
{
    use ValidationTrait;
    use GrpcSupportTrait;
    use ServiceAddressTrait;

    /**
     * @param string $hostname
     * @param array $opts
     *  - 'update_metadata': (optional) a callback function which takes in a
     * metadata array, and returns an updated metadata array
     *  - 'grpc.primary_user_agent': (optional) a user-agent string
     * @param Channel $channel An already created Channel object (optional)
     * @param Interceptor[]|UnaryInterceptorInterface[] $interceptors *EXPERIMENTAL*
     *        Interceptors used to intercept RPC invocations before a call starts.
     *        Please note that implementations of
     *        {@see \Google\ApiCore\Transport\Grpc\UnaryInterceptorInterface} are
     *        considered deprecated and support will be removed in a future
     *        release. To prepare for this, please take the time to convert
     *        `UnaryInterceptorInterface` implementations over to a class which
     *        extends {@see Grpc\Interceptor}.
     * @throws Exception
     */
    public function __construct(string $hostname, array $opts, Channel $channel = null, array $interceptors = [])
    {
        if ($interceptors) {
            $channel = Interceptor::intercept(
                $channel ?: new Channel($hostname, $opts),
                $interceptors
            );
        }

        parent::__construct($hostname, $opts, $channel);
    }

    public function simpleRequest($method, $argument, $deserialize, $metadata = [], $options = [])
    {
        return $this->_simpleRequest($method, $argument, $deserialize, $metadata, $options);
    }

    public function bidiRequest($method, $deserialize, $metadata = [], $options = [])
    {
        return $this->_bidiRequest($method, $deserialize, $metadata, $options);
    }

    public function serverStreamRequest($method, $argument, $deserialize, $metadata = [], $options = [])
    {
        return $this->_serverStreamRequest($method, $argument, $deserialize, $metadata, $options);
    }

    public function clientStreamRequest($method, $deserialize, $metadata = [], $options = [])
    {
        return $this->_clientStreamRequest($method, $deserialize, $metadata, $options);
    }
}
