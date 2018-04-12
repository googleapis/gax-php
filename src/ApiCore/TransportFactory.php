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

use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class TransportFactory
{
    use ValidationTrait;

    const GRPC_DEFAULT_PORT = 443;

    /**
     * Builds a GrpcTransport.
     *
     *
     * @param string $serviceAddress
     *        The address of the API remote host, for example "example.googleapis.com. May also
     *        include the port, for example "example.googleapis.com:443"
     * @param array $config
     *        Config options used to construct the gRPC transport. Supported options are 'stubOpts'
     *        and 'channel'.
     * @return GrpcTransport
     * @throws ValidationException
     */
    public static function buildGrpcTransport($serviceAddress, $config = [])
    {
        $config += [
            'stubOpts' => [],
            'channel'  => null,
        ];
        $host = self::formatGrpcHost($serviceAddress);
        $stubOpts = $config['stubOpts'];
        $channel = $config['channel'];
        return new GrpcTransport($host, $stubOpts, $channel);
    }

    /**
     * Builds a RestTransport.
     *
     * @param string $serviceAddress
     *        The address of the API remote host, for example "example.googleapis.com. May also
     *        include the port, for example "example.googleapis.com:443"
     * @param array $config
     *        Config options used to construct the gRPC transport. Supported options are
     *        'restConfigPath' (required) and 'httpHandler' (optional).
     * @return RestTransport
     * @throws ValidationException
     * @throws \Exception
     */
    public static function buildRestTransport($serviceAddress, $config)
    {
        self::validateNotNull($config, [
            'restConfigPath',
        ]);

        $config += [
            'httpHandler'  => null,
        ];
        $baseUri = self::formatRestBaseUri($serviceAddress);
        $restConfigPath = $config['restConfigPath'];
        $requestBuilder = new RequestBuilder($baseUri, $restConfigPath);
        $httpHandler = $config['httpHandler'] ?: [HttpHandlerFactory::build(), 'async'];
        return new RestTransport($requestBuilder, $httpHandler);
    }

    /**
     * @param string $serviceAddress
     * @return string
     * @throws ValidationException
     */
    private static function formatGrpcHost($serviceAddress)
    {
        list($addr, $port) = self::normalizeServiceAddress($serviceAddress);
        return "$addr:$port";
    }

    /**
     * @param string $serviceAddress
     * @return string
     * @throws ValidationException
     */
    private static function formatRestBaseUri($serviceAddress)
    {
        list($addr, $port) = self::normalizeServiceAddress($serviceAddress);
        return $addr;
    }

    /**
     * @param string $serviceAddress
     * @return array
     * @throws ValidationException
     */
    private static function normalizeServiceAddress($serviceAddress)
    {
        $components = explode(':', $serviceAddress);
        if (count($components) == 2) {
            // Port is included in service address
            return [$components[0], $components[1]];
        } elseif (count($components) == 1) {
            // Port is not included - append default port
            return [$components[0], self::GRPC_DEFAULT_PORT];
        } else {
            throw new ValidationException("Invalid serviceAddress: $serviceAddress");
        }
    }
}
