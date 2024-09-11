<?php
/*
 * Copyright 2018 Google LLC
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

namespace Google\ApiCore\Tests\Unit\Transport;

use Google\ApiCore\ApiException;
use Google\ApiCore\Call;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Testing\MockGrpcTransport;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Tests\Unit\TestTrait;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\ValidationException;
use Google\Auth\Logging\StdOutLogger;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Code;
use Google\Rpc\Status;
use Grpc\BaseStub;
use Grpc\CallInvoker;
use Grpc\ChannelCredentials;
use Grpc\ClientStreamingCall;
use Grpc\ServerStreamingCall;
use Grpc\UnaryCall;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use stdClass;
use TypeError;
use Psr\Log\LoggerInterface;

class GrpcTransportTest extends TestCase
{
    use ProphecyTrait;
    use TestTrait;

    public function setUp(): void
    {
        $this->requiresGrpcExtension();
    }

    private function callCredentialsCallback(MockGrpcTransport $transport)
    {
        $mockCall = new Call('method', '', null);
        $options = [];

        $response = $transport->startUnaryCall($mockCall, $options)->wait();
        $args = $transport->getRequestArguments();
        return call_user_func($args['options']['call_credentials_callback']);
    }

    public function testClientStreamingSuccessObject()
    {
        $response = new Status();
        $response->setCode(Code::OK);
        $response->setMessage('response');

        $status = new stdClass();
        $status->code = Code::OK;

        $clientStreamingCall = $this->prophesize(ClientStreamingCall::class);
        $clientStreamingCall->wait()
            ->shouldBeCalledOnce()
            ->willReturn([$response, $status]);

        $transport = new MockGrpcTransport($clientStreamingCall->reveal());

        $stream = $transport->startClientStreamingCall(
            new Call('method', null),
            []
        );

        /* @var $stream \Google\ApiCore\ClientStream */
        $actualResponse = $stream->writeAllAndReadResponse([]);
        $this->assertEquals($response, $actualResponse);
    }

    public function testClientStreamingFailure()
    {
        $request = 'request';
        $response = 'response';

        $status = new stdClass();
        $status->code = Code::INTERNAL;
        $status->details = 'client streaming failure';

        $clientStreamingCall = $this->prophesize(ClientStreamingCall::class);
        $clientStreamingCall->wait()
            ->shouldBeCalledOnce()
            ->willReturn([$response, $status]);

        $transport = new MockGrpcTransport($clientStreamingCall->reveal());

        $stream = $transport->startClientStreamingCall(
            new Call('takeAction', null),
            []
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('client streaming failure');

        $stream->readResponse();
    }

    public function testServerStreamingSuccess()
    {
        $response = 'response';

        $status = new stdClass();
        $status->code = Code::OK;

        $message = $this->createMockRequest();

        $serverStreamingCall = $this->prophesize(\Grpc\ServerStreamingCall::class);
        $serverStreamingCall->responses()
            ->shouldBeCalledOnce()
            ->willReturn([$response]);
        $serverStreamingCall->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn($status);

        $transport = new MockGrpcTransport($serverStreamingCall->reveal());

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startServerStreamingCall(
            new Call('takeAction', null, $message),
            []
        );

        $actualResponsesArray = [];
        foreach ($stream->readAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }

        $this->assertEquals([$response], $actualResponsesArray);
    }

    public function testServerStreamingSuccessResources()
    {
        $responses = ['resource1', 'resource2'];
        $repeatedField = new RepeatedField(GPBType::STRING);
        foreach ($responses as $response) {
            $repeatedField[] = $response;
        }

        $response = $this->createMockResponse('nextPageToken', $repeatedField);

        $status = new stdClass();
        $status->code = Code::OK;

        $message = $this->createMockRequest();

        $call = $this->prophesize(\Grpc\ServerStreamingCall::class);
        $call->responses()
            ->shouldBeCalledOnce()
            ->willReturn([$response]);
        $call->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn($status);

        $transport = new MockGrpcTransport($call->reveal());

        $call = new Call(
            'takeAction',
            null,
            $message,
            ['resourcesGetMethod' => 'getResourcesList']
        );
        $options = [];

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startServerStreamingCall(
            $call,
            $options
        );

        $actualResponsesArray = [];
        foreach ($stream->readAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals($responses, $actualResponsesArray);
    }

    public function testServerStreamingFailure()
    {
        $status = new stdClass();
        $status->code = Code::INTERNAL;
        $status->details = 'server streaming failure';

        $message = $this->createMockRequest();

        $serverStreamingCall = $this->prophesize(\Grpc\ServerStreamingCall::class);
        $serverStreamingCall->responses()
            ->shouldBeCalledOnce()
            ->willReturn(['response1']);
        $serverStreamingCall->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn($status);

        $transport = new MockGrpcTransport($serverStreamingCall->reveal());

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startServerStreamingCall(
            new Call('takeAction', null, $message),
            []
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('server streaming failure');

        foreach ($stream->readAll() as $actualResponse) {
            // for loop to trigger generator and API exception
        }
    }

    public function testBidiStreamingSuccessSimple()
    {
        $response = 'response';
        $status = new stdClass();
        $status->code = Code::OK;

        $bidiStreamingCall = $this->prophesize(\Grpc\BidiStreamingCall::class);
        $bidiStreamingCall->read()
            ->shouldBeCalled()
            ->willReturn($response, null);
        $bidiStreamingCall->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);
        $bidiStreamingCall->writesDone()
            ->shouldBeCalledOnce();

        $transport = new MockGrpcTransport($bidiStreamingCall->reveal());

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startBidiStreamingCall(
            new Call('takeAction', null),
            []
        );

        $actualResponsesArray = [];
        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals([$response], $actualResponsesArray);
    }

    public function testBidiStreamingSuccessObject()
    {
        $response = new Status();
        $response->setCode(Code::OK);
        $response->setMessage('response');

        $status = new stdClass();
        $status->code = Code::OK;

        $bidiStreamingCall = $this->prophesize(\Grpc\BidiStreamingCall::class);
        $bidiStreamingCall->read()
            ->shouldBeCalled()
            ->willReturn($response, null);
        $bidiStreamingCall->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);
        $bidiStreamingCall->writesDone()
            ->shouldBeCalledOnce();

        $transport = new MockGrpcTransport($bidiStreamingCall->reveal());

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startBidiStreamingCall(
            new Call('takeAction', null),
            []
        );

        $actualResponsesArray = [];
        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals([$response], $actualResponsesArray);
    }

    public function testBidiStreamingSuccessResources()
    {
        $responses = ['resource1', 'resource2'];
        $repeatedField = new RepeatedField(GPBType::STRING);
        foreach ($responses as $response) {
            $repeatedField[] = $response;
        }

        $response = $this->createMockResponse('nextPageToken', $repeatedField);

        $status = new stdClass();
        $status->code = Code::OK;

        $bidiStreamingCall = $this->prophesize(\Grpc\BidiStreamingCall::class);
        $bidiStreamingCall->read()
            ->shouldBeCalled()
            ->willReturn($response, null);
        $bidiStreamingCall->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);
        $bidiStreamingCall->writesDone()
            ->shouldBeCalledOnce();

        $transport = new MockGrpcTransport($bidiStreamingCall->reveal());

        $call = new Call(
            'takeAction',
            null,
            null,
            ['resourcesGetMethod' => 'getResourcesList']
        );

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startBidiStreamingCall(
            $call,
            []
        );

        $actualResponsesArray = [];
        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals($responses, $actualResponsesArray);
    }

    public function testBidiStreamingFailure()
    {
        $response = 'response';
        $status = new stdClass();
        $status->code = Code::INTERNAL;
        $status->details = 'bidi failure';

        $bidiStreamingCall = $this->prophesize(\Grpc\BidiStreamingCall::class);
        $bidiStreamingCall->read()
            ->shouldBeCalled()
            ->willReturn($response, null);
        $bidiStreamingCall->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);
        $bidiStreamingCall->writesDone()
            ->shouldBeCalledOnce();

        $transport = new MockGrpcTransport($bidiStreamingCall->reveal());

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startBidiStreamingCall(
            new Call('takeAction', null),
            []
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('bidi failure');

        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            // for loop to trigger generator and API exception
        }
    }

    public function testAudienceOption()
    {
        $message = $this->createMockRequest();

        $call = $this->prophesize(Call::class);
        $call->getMessage()->willReturn($message);
        $call->getMethod()->shouldBeCalledOnce();
        $call->getDecodeType()->shouldBeCalledOnce();

        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $credentialsWrapper->checkUniverseDomain()
            ->shouldBeCalledOnce();
        $credentialsWrapper->getAuthorizationHeaderCallback('an-audience')
            ->shouldBeCalledOnce();
        $hostname = '';
        $opts = ['credentials' => ChannelCredentials::createInsecure()];
        $transport = new GrpcTransport($hostname, $opts);
        $options = [
            'audience' => 'an-audience',
            'credentialsWrapper' => $credentialsWrapper->reveal(),
        ];
        $transport->startUnaryCall($call->reveal(), $options);
    }

    public function testClientCertSourceOptionValid()
    {
        $mockClientCertSource = function () {
            return 'MOCK_CERT_SOURCE';
        };
        $transport = GrpcTransport::build(
            'address.com:123',
            ['clientCertSource' => $mockClientCertSource]
        );

        $this->assertNotNull($transport);
    }

    public function testClientCertSourceOptionInvalid()
    {
        $this->requiresPhp7();

        $mockClientCertSource = 'foo';

        $this->expectException(TypeError::class);
        $this->expectExceptionMessageMatches('/must be.+callable/i');

        GrpcTransport::build(
            'address.com:123',
            ['clientCertSource' => $mockClientCertSource]
        );
    }

    public function testLoggerGetsCalledIfLoggerSupplied()
    {
        $logger = $this->prophesize(StdOutLogger::class);
        $logger->debug()
            ->shouldBeCalledTimes(2);
        $logger->info()
            ->shouldBeCalledTimes(1);

        $message = $this->createMockRequest();

        $call = $this->prophesize(Call::class);
        $call->getMessage()->willReturn($message);
        $call->getMethod()->shouldBeCalled();
        $call->getDecodeType()->shouldBeCalled();

        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $credentialsWrapper->checkUniverseDomain()
            ->shouldBeCalledOnce();
        $credentialsWrapper->getAuthorizationHeaderCallback('an-audience')
            ->shouldBeCalledOnce();
        $hostname = '';
        $opts = ['credentials' => ChannelCredentials::createInsecure()];
        $transport = new GrpcTransport($hostname, $opts, logger: $logger->reveal());
        $options = [
            'audience' => 'an-audience',
            'credentialsWrapper' => $credentialsWrapper->reveal(),
        ];
        $transport->startUnaryCall($call->reveal(), $options);
    }

    /**
     * @dataProvider buildDataGrpc
     */
    public function testBuildGrpc($apiEndpoint, $config, $expectedTransportProvider)
    {
        $expectedTransport = $expectedTransportProvider();
        $actualTransport = GrpcTransport::build($apiEndpoint, $config);
        $this->assertEquals($expectedTransport, $actualTransport);
    }

    public function buildDataGrpc()
    {
        $uri = 'address.com';
        $apiEndpoint = "$uri:447";
        $apiEndpointDefaultPort = "$uri:443";
        return [
            [
                $apiEndpoint,
                [],
                function () use ($apiEndpoint) {
                    return new GrpcTransport(
                        $apiEndpoint,
                        [
                            'credentials' => null,
                        ],
                        null
                    );
                },
            ],
            [
                $uri,
                [],
                function () use ($apiEndpointDefaultPort) {
                    return new GrpcTransport(
                        $apiEndpointDefaultPort,
                        [
                            'credentials' => null,
                        ],
                        null
                    );
                },
            ],
        ];
    }

    /**
     * @dataProvider buildInvalidData
     */
    public function testBuildInvalid($apiEndpoint, $args)
    {
        $this->expectException(ValidationException::class);

        GrpcTransport::build($apiEndpoint, $args);
    }

    public function buildInvalidData()
    {
        return [
            [
                'addresswithtoo:many:segments',
                [],
            ],
            [
                'example.com',
                [
                    'channel' => 'not a channel',
                ]
            ]
        ];
    }

    /**
     * @dataProvider interceptorDataProvider
     */
    public function testExperimentalInterceptors($callType, $interceptor)
    {
        $transport = new GrpcTransport(
            'example.com',
            [
                'credentials' => ChannelCredentials::createInsecure()
            ],
            null,
            [$interceptor]
        );

        $mockCallInvoker = new MockCallInvoker($this->buildMockCallForInterceptor($callType));

        $r = new \ReflectionProperty(BaseStub::class, 'call_invoker');
        $r->setAccessible(true);
        $r->setValue(
            $transport,
            $mockCallInvoker
        );

        $call = new Call('method1', '', new MockRequest());

        $callMethod = $callType == UnaryCall::class ? 'startUnaryCall' : 'startServerStreamingCall';
        $transport->$callMethod($call, [
            'transportOptions' => [
                'grpcOptions' => [
                    'call-option' => 'call-option-value'
                ]
            ]
        ]);

        $this->assertTrue($mockCallInvoker->wasCalled());
    }

    public function interceptorDataProvider()
    {
        return [
            [
                UnaryCall::class,
                new TestUnaryInterceptor()
            ],
            [
                UnaryCall::class,
                new TestInterceptor()
            ],
            [
                ServerStreamingCall::class,
                new TestInterceptor()
            ]
        ];
    }

    private function buildMockCallForInterceptor($callType)
    {
        $mockCall = $this->prophesize($callType);
        $mockCall->start(
            Argument::type(Message::class),
            [],
            [
                'call-option' => 'call-option-value',
                'test-interceptor-insert' => 'inserted-value'
            ]
        )->shouldBeCalled();

        if ($callType === UnaryCall::class) {
            $mockCall->wait()
                ->willReturn([
                    null,
                    Code::OK
                ]);
        }

        return $mockCall->reveal();
    }
}

class MockCallInvoker implements CallInvoker
{
    private $called = false;
    private $mockCall;

    public function __construct($mockCall)
    {
        $this->mockCall = $mockCall;
    }

    public function createChannelFactory($hostname, $opts)
    {
        // no-op
    }

    public function UnaryCall($channel, $method, $deserialize, $options)
    {
        $this->called = true;
        return $this->mockCall;
    }

    public function ServerStreamingCall($channel, $method, $deserialize, $options)
    {
        $this->called = true;
        return $this->mockCall;
    }

    public function ClientStreamingCall($channel, $method, $deserialize, $options)
    {
        // no-op
    }

    public function BidiStreamingCall($channel, $method, $deserialize, $options)
    {
        // no-op
    }

    public function wasCalled()
    {
        return $this->called;
    }
}
