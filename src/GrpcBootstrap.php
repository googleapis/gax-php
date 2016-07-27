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
namespace Google\GAX;

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\CredentialsLoader;
use Grpc\ChannelCredentials;

/**
 * Class that provides helpful utility functions for bootstrapping a gRPC client.
 */
class GrpcBootstrap
{
    private $authCredentials;

    /**
     * Accept an optional keyFile argument, which can be used to load credentials
     * instead of using ApplicationDefaultCredentials
     */
    public function __construct($scopes, $options = [])
    {
        if (array_key_exists('keyFile', $options) && !is_null($options['keyFile'])) {
            # Get credentials from the keyFile provided
            $jsonData = json_decode(file_get_contents($options['keyFile']), true);
            $this->authCredentials = CredentialsLoader::makeCredentials($scopes, $jsonData);
        } else {
            # Get the Application Default Credentials
            $this->authCredentials = ApplicationDefaultCredentials::getCredentials($scopes);
        }
    }

    /**
     * Creates the callback function to be passed to gRPC for providing the credentials
     * for a call.
     */
    public function createCallCredentialsCallback()
    {
        # Previously a new authCredentials object was created each time a new
        # callback function was created. Instead, the authCredentials object
        # is created when the GrpcBootstrap object is constructed, and is
        # reused.
        #
        # NOTE: currently, this code causes a segmentation fault in gRPC when
        # authCredentials is created using ApplicationDefaultCredentials and
        # it is used more than once. This issue does not occur when using
        # credentials loaded from a json file.
        $authCredentials = $this->authCredentials;
        $callback = function ($context) use ($authCredentials) {
            # This call used to use updateMetadata on an empty array. That is
            # changed here to invoke fetchAuthToken directly, and construct a
            # metadata array manually. This will allow the authCredentials
            # object to be wrapped with a caching implementation.
            $token = $authCredentials->fetchAuthToken();
            return ['Authorization' => array('Bearer ' . $token['access_token'])];
        };
        return $callback;
    }

    /**
     * Gets credentials from ADC. This exists to allow overriding in unit tests.
     */
    protected function getADCCredentials($scopes)
    {
        return ApplicationDefaultCredentials::getCredentials($scopes);
    }

    protected function getKeyFileCredentials($scopes, $keyFile) {
        return CredentialsLoader::makeCredentials(
            $scopes, json_decode(file_get_contents($keyFile), true));
    }

    // TODO(garrettjones):
    // add:
    //   1. (when supported in gRPC) channel
    /**
     * Creates a gRPC client stub.
     *
     * @oaram function $generatedCreateStub
     *        Function callback which must accept two arguments ($hostname, $opts)
     *        and return an instance of the stub of the specific API to call.
     *        Generally, this should just call the stub's constructor and return
     *        the instance.
     * @param string $serviceAddress The domain name of the API remote host.
     * @param mixed $port The port on which to connect to the remote host.
     * @param array $options {
     *     Optional. Options for configuring the gRPC stub.
     *
     *     @type Grpc\ChannelCredentials $sslCreds
     *           A `ChannelCredentials` for use with an SSL-enabled channel.
     *           Default: a credentials object returned from
     *           Grpc\ChannelCredentials::createSsl()
     * }
     */
    public static function createStub($generatedCreateStub, $serviceAddress, $port, $options = array())
    {
        $stubOpts = [];
        if (empty($options['sslCreds'])) {
            $stubOpts['credentials'] = GrpcBootstrap::createSslChannelCredentials();
        } else {
            $stubOpts['credentials'] = $options['sslCreds'];
        }

        $fullAddress = "$serviceAddress:$port";
        $stubOpts['grpc.ssl_target_name_override'] = $fullAddress;

        return $generatedCreateStub($fullAddress, $stubOpts);
    }

    /**
     * Gets credentials from ADC. This exists to allow overriding in unit tests.
     */
    protected static function createSslChannelCredentials()
    {
        return ChannelCredentials::createSsl();
    }
}
