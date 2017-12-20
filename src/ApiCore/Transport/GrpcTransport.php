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

namespace Google\ApiCore\Transport;

use Google\ApiCore\ApiException;
use Google\ApiCore\BidiStream;
use Google\ApiCore\Call;
use Google\ApiCore\ClientStream;
use Google\ApiCore\ServerStream;
use Google\Auth\FetchAuthTokenInterface;
use Google\Protobuf\Internal\Message;
use Google\Rpc\Code;
use Grpc\BaseStub;
use Grpc\Channel;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * A gRPC based transport implementation.
 */
class GrpcTransport extends BaseStub implements TransportInterface
{
    use TransportTrait;

    private $credentialsCallback;

    /**
     * @param string $host The domain name and port of the API remote host.
     * @param FetchAuthTokenInterface $credentialsLoader A credentials loader
     *        used to fetch access tokens.
     * @param array $stubOpts An array of options used when creating a BaseStub.
     * @param Channel $channel An already instantiated channel to be used during
     *        creation of the BaseStub.
     */
    public function __construct(
        $host,
        FetchAuthTokenInterface $credentialsLoader,
        array $stubOpts,
        Channel $channel = null
    ) {
        $this->credentialsCallback = function () use ($credentialsLoader) {
            $token = $credentialsLoader->fetchAuthToken();
            return ['authorization' => ['Bearer ' . $token['access_token']]];
        };

        parent::__construct(
            $host,
            $stubOpts,
            $channel
        );
    }

    /**
     * {@inheritdoc}
     */
    public function startBidiStreamingCall(Call $call, array $options)
    {
        return $this->invokeCallStack(
            function (Call $call, array $options) {
                return $this->doBidiStreamingCall($call, $options);
            },
            $call,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function doBidiStreamingCall(Call $call, array $options)
    {
        return new BidiStream(
            $this->_bidiRequest(
                '/' . $call->getMethod(),
                [$call->getDecodeType(), 'decode'],
                $options->getUserHeaders() ?: [],
                $this->getOptions($options)
            ),
            $call->getDescriptor()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function startClientStreamingCall(Call $call, array $options)
    {
        return $this->invokeCallStack(
            function (Call $call, array $options) {
                return $this->doClientStreamingCall($call, $options);
            },
            $call,
            $options
        );
    }

    public function doClientStreamingCall(Call $call, array $options)
    {
        return new ClientStream(
            $this->_clientStreamRequest(
                '/' . $call->getMethod(),
                [$call->getDecodeType(), 'decode'],
                $options->getUserHeaders() ?: [],
                $this->getOptions($options)
            ),
            $call->getDescriptor()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function startServerStreamingCall(Call $call, array $options)
    {
        return $this->invokeCallStack(
            function (Call $call, array $options) {
                return $this->doServerStreamingCall($call, $options);
            },
            $call,
            $options
        );
    }

    private function doServerStreamingCall(Call $call, array $options)
    {
        $message = $call->getMessage();

        if (!$message) {
            throw new \InvalidArgumentException('A message is required for ServerStreaming calls.');
        }

        return new ServerStream(
            $this->_serverStreamRequest(
                '/' . $call->getMethod(),
                $message,
                [$call->getDecodeType(), 'decode'],
                isset($options['headers']) ? $options['headers'] : [],
                $this->getOptions($options)
            ),
            $call->getDescriptor()
        );
    }

    /**
     * {@inheritdoc}
     */
    private function doUnaryCall(Call $call, array $options)
    {
        $call = $this->_simpleRequest(
            '/' . $call->getMethod(),
            $call->getMessage(),
            [$call->getDecodeType(), 'decode'],
            isset($options['headers']) ? $options['headers'] : [],
            $this->getOptions($options)
        );

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
    }

    private function getOptions(array $options)
    {
        $callOptions = isset($options['transportOptions']['grpcOptions']) ?
            $options['transportOptions']['grpcOptions'] : [];

        $callOptions += ['call_credentials_callback' => $this->credentialsCallback];

        if (isset($options['timeoutMillis'])) {
            $callOptions['timeout'] = $options['timeoutMillis'] * 1000;
        }

        return $callOptions;
    }
}
