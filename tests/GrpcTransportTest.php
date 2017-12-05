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

namespace Google\ApiCore\UnitTests;

use Google\ApiCore\GrpcTransport;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\UnitTests\Mocks\MockGrpcTransport;
use Google\ApiCore\UnitTests\Mocks\MockGrpcChannel;
use Google\Auth\FetchAuthTokenInterface;
use Grpc\ChannelCredentials;
use PHPUnit\Framework\TestCase;

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
        $mockCallSettings = new CallSettings;
        $call = $transport->startCall($mockCall, $mockCallSettings);

        list($_1, $_2, $_3, $_4, $options) = $call->wait();
        return call_user_func($options['call_credentials_callback']);
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
}
