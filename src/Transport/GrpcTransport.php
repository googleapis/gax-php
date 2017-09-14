<?php
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\GAX\Transport;

use Google\GAX\GrpcConstants;
use Google\GAX\CredentialsHelper;
use Google\Cloud\Core\TransportInterface;
use InvalidArgumentException;

/**
 *
 */
class GrpcTransport implements TransportInterface
{
    public function __construct($options = [])
    {
        // Options validation can also be done here, i.e. if $options contains
        // properties for other transports, we can throw an exception!

        // @TODO The GrpcCredentialsHelper class can probably be combined with
        // this class.
        $this->credentialsHelper = new GrpcCredentialsHelper($options);
    }

    public function getStatusCodeNames()
    {
        // For Backwards Compatibility
        return GrpcConstants::getStatusCodeNames();
    }

    /**
     * Constructor.
     *
     * @param array $options {
     *     @type \Grpc\Channel $channel
     *           A `Channel` object to be used by gRPC. If not specified, a channel will be constructed.
     *     @type \Grpc\ChannelCredentials $sslCreds
     *           A `ChannelCredentials` object for use with an SSL-enabled channel.
     *           Default: a credentials object returned from
     *           \Grpc\ChannelCredentials::createSsl()
     *           NOTE: if the $channel optional argument is specified, then this argument is unused.
     *     @type bool $forceNewChannel
     *           If true, this forces gRPC to create a new channel instead of using a persistent channel.
     *           Defaults to false.
     *           NOTE: if the $channel optional argument is specified, then this option is unused.
     * @experimental
     */
    public function createStub($stubClassName, array $options)
    {
        $createStubOptions = [];
        if (array_key_exists('sslCreds', $options)) {
            $createStubOptions['sslCreds'] = $options['sslCreds'];
        }

        if (array_key_exists('createStubFunction', $options)) {
            $createStubFunction = $options['createStubFunction'];
        } else {
            $createStubFunction = function ($hostname, $opts, $channel) use ($stubClassName) {
                return new $stubClassName($hostname, $opts, $channel);
            };
        }

        return $this->credentialsHelper->createStub($createStubFunction);
    }

    /**
     * The GrpcCredentialsHelper class can probably be combined with this class.
     */
    public function createCredentialsCallback()
    {
        return $this->credentialsHelper->createCredentialsCallback();
    }

    public function getName()
    {
        return 'grpc';
    }
}
