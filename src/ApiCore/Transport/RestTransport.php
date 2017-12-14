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
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\RequestBuilder;
use Google\ApiCore\Middleware\AgentHeaderMiddleware;
use Google\ApiCore\Middleware\AuthHeaderMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\Auth\FetchAuthTokenInterface;
use Google\Protobuf\Internal\Message;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * A REST based transport implementation.
 */
class RestTransport implements ApiTransportInterface
{
    private $agentHeaderDescriptor;
    private $credentialsLoader;
    private $httpHandler;
    private $requestBuilder;

    /**
     * @param RequestBuilder $requestBuilder A builder responsible for creating
     *        a PSR-7 request from a set of request information.
     * @param FetchAuthTokenInterface $credentialsLoader A credentials loader
     *        used to fetch access tokens.
     * @param AgentHeaderDescriptor $agentHeaderDescriptor A descriptor containing
     *        the relevant information to build out the user agent header.
     * @param callable $httpHandler A handler used to deliver PSR-7 requests.
     */
    public function __construct(
        RequestBuilder $requestBuilder,
        FetchAuthTokenInterface $credentialsLoader,
        AgentHeaderDescriptor $agentHeaderDescriptor,
        callable $httpHandler
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->credentialsLoader = $credentialsLoader;
        $this->agentHeaderDescriptor = $agentHeaderDescriptor;
        $this->httpHandler = $httpHandler;
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function startClientStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->throwUnsupportedException();
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function startServerStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->throwUnsupportedException();
    }

    /**
     * {@inheritdoc}
     * @throws \BadMethodCallException
     */
    public function startBidiStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $this->throwUnsupportedException();
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable(CallSettings $settings)
    {
        $callable = function (Call $call, CallSettings $settings) {
            $httpHandler = $this->httpHandler;

            return $httpHandler(
                $this->requestBuilder->build(
                    $call->getMethod(),
                    $call->getMessage(),
                    $settings->getUserHeaders()
                ),
                $this->getOptions($settings)
            )->then(
                function (ResponseInterface $response) use ($call) {
                    $decodeType = $call->getDecodeType();
                    $return = new $decodeType;
                    $return->mergeFromJsonString(
                        (string) $response->getBody()
                    );

                    return $return;
                },
                function (\Exception $ex) {
                    if ($ex instanceof RequestException && $ex->hasResponse()) {
                        throw $this->convertToApiException($ex);
                    }

                    throw $ex;
                }
            );
        };

        return new AuthHeaderMiddleware(
            $this->createCallStack($callable, $settings),
            $this->credentialsLoader
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // Nothing to do.
    }

    private function throwUnsupportedException()
    {
        throw new \BadMethodCallException('Streaming calls are not supported while using the REST transport.');
    }

    private function getOptions(CallSettings $settings)
    {
        $transportOptions = $settings->getTransportOptions();
        $options =  isset($transportOptions['rest']) ? $transportOptions['rest'] : [];

        if ($settings->getTimeoutMillis() !== false) {
            $options['timeout'] = $settings->getTimeoutMillis() / 1000;
        }

        return $options;
    }

    private function convertToApiException(\Exception $ex)
    {
        $res = (string) $ex->getResponse()->getBody();
        $rObj = (object) json_decode($res, true)['error'];
        // Overwrite the HTTP Status Code with the RPC code, derived from the "status" field.
        $rObj->code = ApiStatus::rpcCodeFromStatus($rObj->status);
        $rObj->details = $rObj->message;

        return ApiException::createFromStdClass($rObj);
    }

    private function createCallStack(callable $callable, CallSettings $settings)
    {
        $callable = new AgentHeaderMiddleware($callable, $this->agentHeaderDescriptor);
        $callable = new RetryMiddleware($callable);

        return $callable;
    }
}
