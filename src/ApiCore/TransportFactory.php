<?php
/*
 * Copyright 2018, Google Inc.
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

use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Grpc\ChannelCredentials;
use InvalidArgumentException;

class TransportFactory
{
    use ValidationTrait;

    /**
     * Builds a transport given an array of arguments.
     *
     *
     * @param string $serviceAddress The address of the API remote host. Must be formatted as
     *                               "<address>:port", e.g. "my.service.com:443"
     * @param AuthWrapper $authWrapper AuthWrapper to manage auth tokens.
     * @param array  $args {
     *     @type string $transport
     *           Optional. The type of transport to build. Defaults to
     *           'grpc' when available. Supported values: ['grpc', 'rest'].
     *     @type string $restClientConfigPath Path to rest client config.
     *           Required for 'rest' transport only.
     * }
     * @return TransportInterface
     * @throws ValidationException
     */
    public static function build($serviceAddress, $authWrapper, array $args)
    {
        list($uri, $port) = self::validateServiceAddress($serviceAddress);
        $args += [
            'transport' => self::defaultTransport(),
        ];
        $transport = $args['transport'];

        switch ($transport) {
            case 'grpc':
                if (!self::getGrpcDependencyStatus()) {
                    throw new InvalidArgumentException(
                        'gRPC support has been requested but required dependencies ' .
                        'have not been found. For details on how to install the ' .
                        'gRPC extension please see https://cloud.google.com/php/grpc.'
                    );
                }

                $stubOpts = ['credentials'=> self::createSslChannelCredentials()];

                return new GrpcTransport(
                    $serviceAddress,
                    $authWrapper,
                    $stubOpts
                );
            case 'rest':
                self::validateNotNull($args, ['restClientConfigPath']);

                $requestBuilder = new RequestBuilder(
                    $uri,
                    $args['restClientConfigPath']
                );

                try {
                    $httpHandler = [HttpHandlerFactory::build(), 'async'];
                } catch (\Exception $ex) {
                    throw new ValidationException("Failed to create httpHandler", $ex->getCode(), $ex);
                }

                return new RestTransport(
                    $requestBuilder,
                    $authWrapper,
                    $httpHandler
                );

            default:
                throw new ValidationException("Unknown transport type: $transport");
        }
    }

    /**
     * Abstract the checking of the grpc extension for unit testing.
     *
     * @codeCoverageIgnore
     * @return bool
     */
    protected static function getGrpcDependencyStatus()
    {
        return extension_loaded('grpc');
    }

    /**
     * Construct ssl channel credentials. This exists to allow overriding in unit tests.
     *
     * @return ChannelCredentials
     */
    protected static function createSslChannelCredentials()
    {
        return ChannelCredentials::createSsl();
    }

    private static function defaultTransport()
    {
        return self::getGrpcDependencyStatus()
            ? 'grpc'
            : 'rest';
    }

    /**
     * @param $serviceAddress
     * @return array
     * @throws ValidationException
     */
    private static function validateServiceAddress($serviceAddress)
    {
        $components = explode(':', $serviceAddress);
        if (count($components) !== 2) {
            throw new ValidationException(
                'Invalid serviceAddress. Expected format "<address>:<port>", got ' . $serviceAddress);
        }
        return $components;
    }
}
