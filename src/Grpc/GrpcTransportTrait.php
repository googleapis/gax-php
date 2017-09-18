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
namespace Google\GAX\Grpc;

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenCache;
use Google\Auth\Cache\MemoryCacheItemPool;
use Google\GAX\ValidationTrait;
use Grpc\ChannelCredentials;
use InvalidArgumentException;

trait GrpcTransportTrait
{
    use ValidationTrait;

    private $grpcStub;
    private $credentialsCallback;

    // This value is expected to be used on implementing classes
    private $grpcStubClassName;

    /**
     * Accepts an optional credentialsLoader argument, to be used instead of using
     * the ApplicationDefaultCredentials
     *
     * @param array $args {
     *     Required. An array of required and optional arguments. Arguments in addition to those documented below
     *     will be passed as optional arguments to Google\Auth\FetchAuthTokenCache when caching is enabled.
     *
     *     @type string $serviceAddress
     *           Required. The domain name of the API remote host.
     *     @type mixed $port
     *           Required. The port on which to connect to the remote host.
     *     @type string[] $scopes
     *           Optional. A list of scopes required for API access.
     *           Exactly one of $scopes or $credentialsLoader must be provided.
     *           NOTE: if $credentialsLoader is provided, this argument is ignored.
     *     @type \Google\Auth\CredentialsLoader $credentialsLoader
     *           Optional. A user-created CredentialsLoader object. Defaults to using
     *           ApplicationDefaultCredentials with the provided $scopes argument.
     *           Exactly one of $scopes or $credentialsLoader must be provided.
     *     @type \Grpc\Channel $channel
     *           Optional. A `Channel` object to be used by gRPC. If not specified, a channel will be constructed.
     *     @type \Grpc\ChannelCredentials $sslCreds
     *           Optional. A `ChannelCredentials` object for use with an SSL-enabled channel.
     *           Default: a credentials object returned from
     *           \Grpc\ChannelCredentials::createSsl()
     *           NOTE: if the $channel optional argument is specified, then this option is unused.
     *     @type bool $forceNewChannel
     *           Optional. If true, this forces gRPC to create a new channel instead of using a persistent channel.
     *           Defaults to false.
     *           NOTE: if the $channel optional argument is specified, then this option is unused.
     *     @type boolean $enableCaching
     *           Optional. Enable caching of access tokens. Defaults to true.
     * }
     */
    public function __construct($args)
    {
        $this->validateNotNull($args, [
            'serviceAddress',
            'port',
        ]);

        $defaultOptions = [
            'forceNewChannel' => false,
            'enableCaching' => true,
        ];
        $args = array_merge($defaultOptions, $args);

        if (empty($args['credentialsLoader'])) {
            $this->validateNotNull($args, ['scopes']);
            $args['credentialsLoader'] = $this->getADCCredentials($args['scopes']);
        }

        if ($args['enableCaching']) {
            $cachingOptions = array_diff_key($args, array_flip([
                'serviceAddress',
                'port',
                'scopes',
                'credentialsLoader',
                'channel',
                'sslCreds',
                'forceNewChannel',
                'enableCaching'
            ]));
            $args['credentialsLoader'] = new FetchAuthTokenCache(
                $args['credentialsLoader'],
                $cachingOptions,
                new MemoryCacheItemPool()
            );
        }

        $credentialsLoader = $this->args['credentialsLoader'];
        $this->credentialsCallback = function () use ($credentialsLoader) {
            $token = $credentialsLoader->fetchAuthToken();
            return ['authorization' => array('Bearer ' . $token['access_token'])];
        };

        $stubOpts = [];
        // We need to use array_key_exists here because null is a valid value
        if (!array_key_exists('sslCreds', $args)) {
            $stubOpts['credentials'] = $this->createSslChannelCredentials();
        } else {
            $stubOpts['credentials'] = $args['sslCreds'];
        }

        $serviceAddress = $args['serviceAddress'];
        $port = $args['port'];
        $fullAddress = "$serviceAddress:$port";
        $stubOpts['grpc.ssl_target_name_override'] = $fullAddress;

        if (isset($args['channel'])) {
            $channel = $args['channel'];
        } else {
            $channel = null;
        }

        if (isset($args['forceNewChannel']) && $args['forceNewChannel']) {
            $stubOpts['force_new'] = true;
        }
        $this->grpcStub = new self::$grpcStubClassName($fullAddress, $stubOpts, $channel);
    }

    private function constructGrpcArgs($optionalArgs = [])
    {
        $metadata = [];
        $options = [];
        if (array_key_exists('timeoutMillis', $optionalArgs)) {
            $options['timeout'] = $optionalArgs['timeoutMillis'] * 1000;
        }
        if (array_key_exists('headers', $optionalArgs)) {
            $metadata = $optionalArgs['headers'];
        }
        $options['call_credentials_callback'] = $this->credentialsCallback;
        if (array_key_exists('credentialsLoader', $optionalArgs)) {
            $credentialsLoader = $optionalArgs['credentialsLoader'];
            $callback = function () use ($credentialsLoader) {
                $token = $credentialsLoader->fetchAuthToken();
                return ['authorization' => array('Bearer ' . $token['access_token'])];
            };
            $options['call_credentials_callback'] = $callback;
        }
        return [$metadata, $options];
    }

    /**
     * Gets credentials from ADC. This exists to allow overriding in unit tests.
     *
     * @param string[] $scopes
     * @return CredentialsLoader
     */
    protected function getADCCredentials($scopes)
    {
        return ApplicationDefaultCredentials::getCredentials($scopes);
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
