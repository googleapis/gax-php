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

namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\GrpcTransport;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\Tests\Mocks\MockGrpcTransport;
use Google\Auth\FetchAuthTokenInterface;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBType;
use Google\Rpc\Code;
use Grpc\ChannelCredentials;
use PHPUnit\Framework\TestCase;
use stdClass;

class GrpcTransportTest extends TestCase
{
    use TestTrait;

    public function setUp()
    {
        $this->checkAndSkipGrpcTests();
    }

    private function callCredentialsCallback(MockGrpcTransport $transport)
    {
        $mockCall = new Call('method', [], null);
        $mockCallSettings = new CallSettings();
        $call = $transport->startCall($mockCall, $mockCallSettings);

        $call->wait();
        $args = $transport->getRequestArguments();
        return call_user_func($args['options']['call_credentials_callback']);
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Missing required argument scopes
     */
    public function testScopesRequired()
    {
        // NOTE: scopes is only required if credentialsLoader is not provided
        new GrpcTransport('host', []);
    }

    public function testConstructGrpcArgsCustomCredsLoader()
    {
        $credentialsLoader = $this->getMock(FetchAuthTokenInterface::class);
        $credentialsLoader->expects($this->once())
            ->method('fetchAuthToken')
            ->willReturn(['access_token' => 'accessToken']);

        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'credentialsLoader' => $credentialsLoader,
        ]);
        $grpcTransport->setMockCall($this->createMockCall());
        $callbackResult = $this->callCredentialsCallback($grpcTransport);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
    }

    public function testCreateCallCredentialsCallbackDisableCaching()
    {
        $credentialsLoader = $this->getMock(FetchAuthTokenInterface::class);
        $credentialsLoader->expects($this->exactly(2))
            ->method('fetchAuthToken')
            ->will($this->onConsecutiveCalls(
                ['access_token' => 'accessToken'],
                ['access_token' => 'accessToken2']
            ));

        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'credentialsLoader' => $credentialsLoader,
            'enableCaching' => false,
        ]);
        $grpcTransport->setMockCall($this->createMockCall());
        $callbackResult = $this->callCredentialsCallback($grpcTransport);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
        $callbackResult = $this->callCredentialsCallback($grpcTransport);
        $this->assertEquals(['Bearer accessToken2'], $callbackResult['authorization']);
    }

    public function testCreateCallCredentialsCallbackCaching()
    {
        $credentialsLoader = $this->getMock(FetchAuthTokenInterface::class);
        $credentialsLoader->expects($this->once())
            ->method('fetchAuthToken')
            ->willReturn(['access_token' => 'accessToken']);
        $credentialsLoader->expects($this->exactly(2))
            ->method('getCacheKey')
            ->willReturn('cacheKey');

        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'credentialsLoader' => $credentialsLoader,
        ]);
        $grpcTransport->setMockCall($this->createMockCall());
        $callbackResult = $this->callCredentialsCallback($grpcTransport);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
        $callbackResult = $this->callCredentialsCallback($grpcTransport);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
    }

    public function testCreateWithHostname()
    {
        $sslCreds = new \Grpc\ChannelCredentials;
        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope'],
            'sslCreds' => $sslCreds,
        ]);
        $this->assertEquals('my-service-address:8443', $grpcTransport->getHostname());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage credentials must be a ChannelCredentials object
     */
    public function testCreateCStubWithInvalidChannelCredentials()
    {
        $invalidCreds = 'invalid-creds';
        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope'],
            'sslCreds' => $invalidCreds,
        ]);
    }

    public function testCreateWithExplicitSslCreds()
    {
        $sslCreds = new \Grpc\ChannelCredentials;
        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope'],
            'sslCreds' => $sslCreds,
        ]);
        // If no exception is thrown, we're good. It's not a very solid test, but
        // because Grpc\Channel is part of the C extension, there is no other way
        // to test this.
    }

    public function testCreateStubWithInsecureSslCreds()
    {
        $insecureCreds = ChannelCredentials::createInsecure();
        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope'],
            'sslCreds' => $insecureCreds,
        ]);
        // If no exception is thrown, we're good. It's not a very solid test, but
        // because Grpc\Channel is part of the C extension, there is no other way
        // to test this.
    }

    public function testCreateStubWithChannel()
    {
        $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope'],
            'channel' => $channel = $this->getMockBuilder('Grpc\Channel')
                ->disableOriginalConstructor()
                ->getMock(),
        ]);
        $this->assertEquals($channel, $grpcTransport->getChannel());
    }

    public function testCreateStubWithForceNew()
    {
        // Because Grpc\Channel is part of the C extension, there is no way to
        // test this.

        // $grpcTransport = new MockGrpcTransport('my-service-address:8443', [
        //     'forceNewChannel' => true
        // ]);
    }

    public function testClientStreamingSuccessObject()
    {
        $response = new \Google\Rpc\Status();
        $response->setCode(\Google\Rpc\Code::OK);
        $response->setMessage('response');

        $status = new stdClass;
        $status->code = Code::OK;

        $call = $this->getMockBuilder(\Grpc\ClientStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('write');
        $call->method('wait')
            ->will($this->returnValue([$response, $status]));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);

        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'ClientStreaming']
        );

        /* @var $stream \Google\ApiCore\ClientStreamInterface */
        $actualResponse = $stream->writeAllAndReadResponse([]);
        $this->assertEquals($response, $actualResponse);
    }

    /**
     * @expectedException \Google\ApiCore\ApiException
     * @expectedExceptionMessage client streaming failure
     */
    public function testClientStreamingFailure()
    {
        $request = "request";
        $response = "response";

        $status = new stdClass;
        $status->code = Code::INTERNAL;
        $status->details = 'client streaming failure';

        $call = $this->getMockBuilder(\Grpc\ClientStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('wait')
            ->will($this->returnValue([$response, $status]));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'ClientStreaming']
        );

        $stream->readResponse();
    }

    public function testServerStreamingSuccess()
    {
        $response = "response";

        $status = new stdClass;
        $status->code = Code::OK;

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $call = $this->getMockBuilder(\Grpc\ServerStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('responses')
            ->will($this->returnValue([$response]));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null, $message);

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'ServerStreaming']
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

        $status = new stdClass;
        $status->code = Code::OK;

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $call = $this->getMockBuilder(\Grpc\ServerStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('responses')
            ->will($this->returnValue([$response]));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null, $message);

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'ServerStreaming', 'resourcesGetMethod' => 'getResourcesList']
        );

        $actualResponsesArray = [];
        foreach ($stream->readAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals($responses, $actualResponsesArray);
    }

    /**
     * @expectedException \Google\ApiCore\ApiException
     * @expectedExceptionMessage server streaming failure
     */
    public function testServerStreamingFailure()
    {
        $status = new stdClass;
        $status->code = Code::INTERNAL;
        $status->details = 'server streaming failure';

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $call = $this->getMockBuilder(\Grpc\ServerStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('responses')
            ->will($this->returnValue(['response1']));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null, $message);

        /* @var $stream \Google\ApiCore\ServerStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'ServerStreaming']
        );

        foreach ($stream->readAll() as $actualResponse) {
            // for loop to trigger generator and API exception
        }
    }

    public function testBidiStreamingSuccessSimple()
    {
        $response = "response";
        $status = new stdClass;
        $status->code = Code::OK;

        $call = $this->getMockBuilder(\Grpc\BidiStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('read')
            ->will($this->onConsecutiveCalls($response, null));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'BidiStreaming']
        );

        $actualResponsesArray = [];
        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals([$response], $actualResponsesArray);
    }

    public function testBidiStreamingSuccessObject()
    {
        $response = new \Google\Rpc\Status();
        $response->setCode(Code::OK);
        $response->setMessage('response');

        $status = new stdClass;
        $status->code = Code::OK;

        $call = $this->getMockBuilder(\Grpc\BidiStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('read')
            ->will($this->onConsecutiveCalls($response, null));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'BidiStreaming']
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

        $status = new stdClass;
        $status->code = Code::OK;

        $call = $this->getMockBuilder(\Grpc\BidiStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('read')
            ->will($this->onConsecutiveCalls($response, null));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'BidiStreaming', 'resourcesGetMethod' => 'getResourcesList']
        );

        $actualResponsesArray = [];
        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            $actualResponsesArray[] = $actualResponse;
        }
        $this->assertEquals($responses, $actualResponsesArray);
    }

    /**
     * @expectedException \Google\ApiCore\ApiException
     * @expectedExceptionMessage bidi failure
     */
    public function testBidiStreamingFailure()
    {
        $response = "response";
        $status = new stdClass;
        $status->code = Code::INTERNAL;
        $status->details = 'bidi failure';

        $call = $this->getMockBuilder(\Grpc\BidiStreamingCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $call->method('read')
            ->will($this->onConsecutiveCalls($response, null));
        $call->method('getStatus')
            ->will($this->returnValue($status));

        $transport = new MockGrpcTransport('my-service-address:8443', [
            'scopes' => ['my-scope']
        ]);
        $transport->setMockCall($call);

        $callSettings = new CallSettings([]);
        $call = new Call('takeAction', null);

        /* @var $stream \Google\ApiCore\BidiStream */
        $stream = $transport->startStreamingCall(
            $call,
            $callSettings,
            ['streamingType' => 'BidiStreaming']
        );

        foreach ($stream->closeWriteAndReadAll() as $actualResponse) {
            // for loop to trigger generator and API exception
        }
    }
}
