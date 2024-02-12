<?php
/*
 * Copyright 2024 Google LLC
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

use Google\ApiCore\AgentHeader;
use Google\ApiCore\BidiStream;
use Google\ApiCore\Call;
use Google\ApiCore\CallHandler;
use Google\ApiCore\ClientStream;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\ClientInterface;
use Google\ApiCore\TransportSupportTrait;
use Google\ApiCore\Descriptor\ServiceDescriptor;
use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\Middleware\MiddlewareInterface;
use Google\ApiCore\OperationResponse;
use Google\ApiCore\RequestParamsHeaderDescriptor;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\ServerStream;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Testing\MockRequestBody;
use Google\ApiCore\Testing\MockResponse;
use Google\ApiCore\Transport\GrpcFallbackTransport;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\ValidationException;
use Google\LongRunning\Operation;
use Google\Longrunning\ListOperationsResponse;
use Grpc\Gcp\Config;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class TransportSupportTraitTest extends TestCase
{
    use ProphecyTrait;
    use TestTrait;

    private const BASIC_TRANSPORT_CONFIG = [
        'rest' => [
            'restClientConfigPath' => __DIR__ . '/testdata/test_service_rest_client_config.php',
        ],
    ];

    /**
     * @dataProvider createTransportData
     */
    public function testCreateTransport($transport, $expectedTransportClass)
    {
        if ($expectedTransportClass == GrpcTransport::class) {
            $this->requiresGrpcExtension();
        }
        $client = new TransportSupportTraitImpl();
        $transport = $client->createTransport(
            'address:332',
            $transport,
            self::BASIC_TRANSPORT_CONFIG
        );

        $this->assertEquals($expectedTransportClass, get_class($transport));
    }

    public function createTransportData()
    {
        $defaultTransportClass = extension_loaded('grpc')
            ? GrpcTransport::class
            : RestTransport::class;
        $transport = extension_loaded('grpc')
            ? 'grpc'
            : 'rest';
        return [
            [$transport, $defaultTransportClass],
            ['grpc', GrpcTransport::class],
            ['rest', RestTransport::class],
            ['grpc-fallback', GrpcFallbackTransport::class],
        ];
    }

    /**
     * @dataProvider createTransportDataInvalid
     */
    public function testCreateTransportInvalid($transport, $transportConfig)
    {
        $this->expectException(ValidationException::class);

        $client = new TransportSupportTraitImpl();
        $client->createTransport(
            'address:443',
            $transport,
            $transportConfig
        );
    }

    public function createTransportDataInvalid()
    {
        return [
            [null, self::BASIC_TRANSPORT_CONFIG],
            [['transport' => 'weirdstring'], self::BASIC_TRANSPORT_CONFIG],
            [['transport' => new \stdClass()], self::BASIC_TRANSPORT_CONFIG],
            [['transport' => 'rest'], []],
        ];
    }

    public function testGetTransport()
    {
        $transport = $this->prophesize(TransportInterface::class)->reveal();
        $client = new TransportSupportTraitImpl($transport);
        $this->assertEquals($transport, $client->getTransport());
    }

    public function testInvalidTransport()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unexpected transport option "foo".');

        $client = new TransportSupportTraitImpl();
        $client->createTransport('address:443', 'foo', []);
    }

    public function testInvalidTransportOverride()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unexpected transport option "grpc". Supported transports: rest');

        $client = new TransportSupportTraitRestOnlyImpl();
        $client->createTransport('address:443', 'grpc', []);
    }
}

class TransportSupportTraitImpl
{
    use TransportSupportTrait {
        createTransport as public;
        getTransport as public;
    }

    public function __construct($transport = null)
    {
        $this->transport = $transport;
    }
}

class TransportSupportTraitRestOnlyImpl
{
    use TransportSupportTrait {
        createTransport as public;
    }

    private function supportedTransports()
    {
        return ['rest'];
    }
}
