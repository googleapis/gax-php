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

namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\AgentHeaderDescriptor;
use Google\ApiCore\Call;
use Google\ApiCore\GapicClientTrait;
use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\RequestBuilder;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\TransportFactory;
use Google\ApiCore\ValidationException;
use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase;

class TransportFactoryTest extends TestCase
{
    /**
     * @dataProvider buildData
     */
    public function testBuild($serviceAddress, $args, $expectedTransport)
    {
        $actualTransport = TransportFactory::build($serviceAddress, $args);
        $this->assertEquals($expectedTransport, $actualTransport);
    }

    public function buildData()
    {
        $uri = "address.com";
        $serviceAddress = "$uri:443";
        $restConfigPath = __DIR__ . '/testdata/test_service_rest_client_config.php';
        $requestBuilder = new RequestBuilder($uri, $restConfigPath);
        $credentialsLoader = ApplicationDefaultCredentials::getCredentials(['customscope'], null);
        $httpHandler = function () {};
        $authHttpHandler = function () {};
        return [
            [
                $serviceAddress,
                [
                    'transport' => 'rest',
                    'restClientConfigPath' => $restConfigPath,
                    'credentialsLoader' => $credentialsLoader,
                    'httpHandler' => $httpHandler,
                    'authHttpHandler' => $authHttpHandler,
                ],
                new RestTransport($requestBuilder, $credentialsLoader, $httpHandler, $authHttpHandler)
            ],
            [
                $serviceAddress,
                [
                    'transport' => 'grpc',
                    'restClientConfigPath' => $restConfigPath,
                    'credentialsLoader' => $credentialsLoader,
                    'httpHandler' => $httpHandler,
                    'authHttpHandler' => $authHttpHandler,
                ],
                new GrpcTransport(
                    $serviceAddress,
                    $credentialsLoader,
                    $authHttpHandler,
                    [
                        'credentials' => null,
                        'force_new' => false
                    ],
                    null),
            ],
        ];
    }
}
