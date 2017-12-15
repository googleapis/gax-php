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

use Google\ApiCore\AgentHeaderDescriptor;
use Google\ApiCore\ApiException;
use Google\ApiCore\BidiStream;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\ClientStream;
use Google\ApiCore\ServerStream;
use Google\ApiCore\Middleware\AgentHeaderMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
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
class GrpcTransport extends BaseStub implements ApiTransportInterface
{
    private $agentHeaderDescriptor;
    private $credentialsCallback;

    /**
     * @param string $host The domain name and port of the API remote host.
     * @param FetchAuthTokenInterface $credentialsLoader A credentials loader
     *        used to fetch access tokens.
     * @param AgentHeaderDescriptor $agentHeaderDescriptor A descriptor containing
     *        the relevant information to build out the user agent header.
     * @param array $stubOpts An array of options used when creating a BaseStub.
     * @param Channel $channel An already instantiated channel to be used during
     *        creation of the BaseStub.
     */
    public function __construct(
        $host,
        FetchAuthTokenInterface $credentialsLoader,
        AgentHeaderDescriptor $agentHeaderDescriptor,
        array $stubOpts,
        Channel $channel = null
    ) {
        $this->agentHeaderDescriptor = $agentHeaderDescriptor;
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
    public function startBidiStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->validateStreamingApiCallSettings($settings);

        return $this->createCallStack(
            function (Call $call, CallSettings $settings) use ($descriptor) {
                return new BidiStream(
                    $this->_bidiRequest(
                        $call->getMethod(),
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    ),
                    $descriptor
                );
            },
            $settings
        );
    }

    /**
     * {@inheritdoc}
     */
    public function startClientStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->validateStreamingApiCallSettings($settings);

        return $this->createCallStack(
            function (Call $call, CallSettings $settings) use ($descriptor) {
                return new ClientStream(
                    $this->_clientStreamRequest(
                        $call->getMethod(),
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    ),
                    $descriptor
                );
            },
            $settings
        );
    }

    /**
     * {@inheritdoc}
     */
    public function startServerStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->validateStreamingApiCallSettings($settings);
        $message = $call->getMessage();

        if (!$message) {
            throw new \InvalidArgumentException('A message is required for ServerStreaming calls.');
        }

        return $this->createCallStack(
            function (Call $call, CallSettings $settings) use ($descriptor) {
                return new ServerStream(
                    $this->_serverStreamRequest(
                        $call->getMethod(),
                        $message,
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    ),
                    $descriptor
                );
            },
            $settings
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable(CallSettings $settings)
    {
        $callable = function (Call $call, CallSettings $settings) {
            $call = $this->_simpleRequest(
                '/' . $call->getMethod(),
                $call->getMessage(),
                [$call->getDecodeType(), 'decode'],
                $settings->getUserHeaders() ?: [],
                $this->getOptions($settings)
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
        };

        return $this->createCallStack($callable, $settings);
    }

    private function getOptions(CallSettings $settings)
    {
        $transportOptions = $settings->getTransportOptions();
        $options = isset($transportOptions['grpc']) ? $transportOptions['grpc'] : [];
        $options += ['call_credentials_callback' => $this->credentialsCallback];

        if ($timeout = $settings->getTimeoutMillis()) {
            $options['timeout'] = $timeout * 1000;
        }

        return $options;
    }

    private function validateStreamingApiCallSettings(CallSettings $settings)
    {
        $retrySettings = $settings->getRetrySettings();

        if (!is_null($retrySettings) && $retrySettings->retriesEnabled()) {
            throw new ValidationException(
                'grpcStreamingDescriptor not compatible with retry settings'
            );
        }
    }

    private function createCallStack(callable $callable, CallSettings $settings)
    {
        $callable = new AgentHeaderMiddleware($callable, $this->agentHeaderDescriptor);
        $callable = new RetryMiddleware($callable);

        return $callable;
    }
}
