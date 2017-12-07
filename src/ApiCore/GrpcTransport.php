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

use Google\Auth\Cache\MemoryCacheItemPool;
use Google\Auth\FetchAuthTokenInterface;
use Google\Protobuf\Internal\Message;
use Google\Rpc\Code;
use Grpc\BaseStub;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

class GrpcTransport extends BaseStub implements ApiTransportInterface
{
    use ApiTransportTrait;
    use ValidationTrait;

    private $credentialsCallback;

    /**
     * @param string $host The domain name and port of the API remote host.
     * @param array $options {
     *     Optional.
     *
     *     @type string[] $scopes
     *           A list of scopes required for API access. Exactly one of
     *           $scopes or $credentialsLoader must be provided.
     *           NOTE: if $credentialsLoader is provided, this argument is
     *           ignored.
     *     @type FetchAuthTokenInterface $credentialsLoader
     *           A user-created CredentialsLoader object. Defaults to using
     *           ApplicationDefaultCredentials with the provided $scopes argument.
     *           Exactly one of $scopes or $credentialsLoader must be provided.
     *     @type Channel $channel
     *           A `Channel` object to be used by gRPC. If not specified, a
     *           channel will be constructed.
     *     @type ChannelCredentials $sslCreds
     *           A `ChannelCredentials` object for use with an SSL-enabled
     *           channel. Default: a credentials object returned from
     *           \Grpc\ChannelCredentials::createSsl()
     *           NOTE: if the $channel optional argument is specified, then this
     *           option is unused.
     *     @type bool $forceNewChannel
     *           If true, this forces gRPC to create a new channel instead of
     *           using a persistent channel. Defaults to false.
     *           NOTE: if the $channel optional argument is specified, then this
     *           option is unused.
     *     @type boolean $enableCaching
     *           Enable caching of access tokens. Defaults to true.
     *     @type CacheItemPoolInterface $authCache
     *           A cache for storing access tokens. Defaults to a simple in
     *           memory implementation.
     *     @type array $authCacheOptions
     *           Cache configuration options.
     *     @type callable $authHttpHandler
     *           A handler used to deliver Psr7 requests specifically for
     *           authentication.
     * }
     */
    public function __construct($host, array $options = [])
    {
        $options = $this->setCommonDefaults($options + [
            'forceNewChannel' => false
        ]);

        $credentialsLoader = $options['credentialsLoader'];
        $this->credentialsCallback = function () use ($credentialsLoader) {
            $token = $credentialsLoader->fetchAuthToken();
            return ['authorization' => ['Bearer ' . $token['access_token']]];
        };

        $stubOpts = [
            'force_new' => $options['forceNewChannel']
        ];
        // We need to use array_key_exists here because null is a valid value
        if (!array_key_exists('sslCreds', $options)) {
            $stubOpts['credentials'] = $this->createSslChannelCredentials();
        } else {
            $stubOpts['credentials'] = $options['sslCreds'];
        }

        parent::__construct(
            $host,
            $stubOpts,
            $this->pluck('channel', $options, false)
        );
    }

    /**
     * @param Call $call
     * @param CallSettings $settings The call settings to use for this call.
     * @param string $streamingType
     * @param string $resourcesGetMethod
     *
     * @return StreamingCallInterface
     * @todo interface for streaming calls?
     * @todo pass along resourceGetMethod
     */
    public function startStreamingCall(Call $call, CallSettings $settings, array $descriptor)
    {
        $streamingType = $this->pluck('streamingType', $descriptor);
        $resourcesGetMethod = $this->pluck('resourcesGetMethod', $descriptor, false);
        $this->validateStreamingApiCallSettings($settings);

        switch ($streamingType) {
            case 'ClientStreaming':
                return new ClientStream(
                    $this->_clientStreamRequest(
                        $call->getMethod(),
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    )
                );
            case 'ServerStreaming':
                $message = $call->getMessage();

                if (!$message) {
                    throw new \Exception('$message is required for ServerStreaming calls.');
                }

                return new ServerStream(
                    $this->_serverStreamRequest(
                        $call->getMethod(),
                        $message,
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    )
                );
            case 'BidiStreaming':
                return new BidiStream(
                    $this->_bidiRequest(
                        $call->getMethod(),
                        $call->getDecodeType(),
                        $settings->getUserHeaders() ?: [],
                        $this->getOptions($settings)
                    )
                );
            default:
                throw new ValidationException("Unexpected gRPC streaming type: $streamingType");
        }
    }

    private function getOptions(CallSettings $settings)
    {
        $retrySettings = $settings->getRetrySettings();
        $options = $settings->getGrpcOptions() ?: []
            + ['call_credentials_callback' => $this->credentialsCallback];

        if ($retrySettings && $retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
            $options['timeout'] = $retrySettings->getNoRetriesRpcTimeoutMillis() * 1000;
        }

        return $options;
    }

    private function getCallable(CallSettings $settings)
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

    private function validateStreamingApiCallSettings(CallSettings $settings)
    {
        $retrySettings = $settings->getRetrySettings();

        if (!is_null($retrySettings) && $retrySettings->retriesEnabled()) {
            throw new ValidationException(
                'grpcStreamingDescriptor not compatible with retry settings'
            );
        }
    }

    /**
     * Construct ssl channel credentials. This exists to allow overriding in unit tests.
     *
     * @return \Grpc\ChannelCredentials
     */
    protected function createSslChannelCredentials()
    {
        return ChannelCredentials::createSsl();
    }
}
