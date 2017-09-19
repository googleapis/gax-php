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
namespace Google\GAX\UnitTests;

use PHPUnit_Framework_TestCase;
use Google\GAX\Grpc\GrpcTransportTrait;
use Google\GAX\UnitTests\Mocks\MockCredentialsLoader;

class GrpcTransportTraitTest extends PHPUnit_Framework_TestCase
{
    private $defaultScope = ['my-scope'];
    private $defaultTokens = [
        [
            'access_token' => 'accessToken',
            'expires_in' => '100',
        ],
        [
            'access_token' => 'accessToken2',
            'expires_in' => '100'
        ],
    ];

    /**
     * @expectedException \Google\GAX\ValidationException
     */
    public function testServiceAddressRequired()
    {
        new MockGrpcTransport([
            'port' => 8443,
            'scopes' => $this->defaultScope,
        ]);
    }

    /**
     * @expectedException \Google\GAX\ValidationException
     */
    public function testPortRequired()
    {
        new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'scopes' => $this->defaultScope,
        ]);
    }

    /**
     * @expectedException \Google\GAX\ValidationException
     */
    public function testScopesRequired()
    {
        // NOTE: scopes is only required if credentialsLoader is not provided
        new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
        ]);
    }

    public function testConstructGrpcArgsDefault()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
        ]);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer adcAccessToken'], $callbackResult['authorization']);
    }

    public function testConstructGrpcArgsCustomCredsLoader()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'credentialsLoader' => new MockCredentialsLoader(
                $this->defaultScope,
                $this->defaultTokens
            )
        ]);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
    }

    public function testCreateCallCredentialsCallbackDisableCaching()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'credentialsLoader' => new MockCredentialsLoader(
                $this->defaultScope,
                $this->defaultTokens
            ),
            'enableCaching' => false,
        ]);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer accessToken2'], $callbackResult['authorization']);
    }

    public function testCreateCallCredentialsCallbackCaching()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'credentialsLoader' => new MockCredentialsLoader(
                $this->defaultScope,
                $this->defaultTokens
            ),
        ]);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
        list($request, $options) = $grpcTransportImpl->doConstructGrpcArgs();
        $callbackResult = call_user_func($options['call_credentials_callback']);
        $this->assertEquals(['Bearer accessToken'], $callbackResult['authorization']);
    }

    public function testCreateStubWithDefaultSslCreds()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
        ]);
        $stub = $grpcTransportImpl->getGrpcStub();
        $this->assertEquals('my-service-address:8443', $stub->hostname);
        $this->assertEquals('DummySslCreds', $stub->stubOpts['credentials']);
        $this->assertEquals(
            'my-service-address:8443',
            $stub->stubOpts['grpc.ssl_target_name_override']
        );
        $this->assertNull($stub->channel);
    }

    public function testCreateStubWithExplicitSslCreds()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
            'sslCreds' => 'provided-creds',
        ]);
        $stub = $grpcTransportImpl->getGrpcStub();
        $this->assertEquals('my-service-address:8443', $stub->hostname);
        $this->assertEquals('provided-creds', $stub->stubOpts['credentials']);
        $this->assertEquals(
            'my-service-address:8443',
            $stub->stubOpts['grpc.ssl_target_name_override']
        );
        $this->assertNull($stub->channel);
    }

    public function testCreateStubWithInsecureSslCreds()
    {
        $insecureCreds = \Grpc\ChannelCredentials::createInsecure();
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
            'sslCreds' => $insecureCreds,
        ]);
        $stub = $grpcTransportImpl->getGrpcStub();
        $this->assertEquals('my-service-address:8443', $stub->hostname);
        $this->assertEquals($insecureCreds, $stub->stubOpts['credentials']);
        $this->assertEquals(
            'my-service-address:8443',
            $stub->stubOpts['grpc.ssl_target_name_override']
        );
        $this->assertNull($stub->channel);
    }

    public function testCreateStubWithChannel()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
            'channel' => 'my-channel'
        ]);
        $stub = $grpcTransportImpl->getGrpcStub();
        $this->assertEquals('my-service-address:8443', $stub->hostname);
        $this->assertEquals('DummySslCreds', $stub->stubOpts['credentials']);
        $this->assertEquals(
            'my-service-address:8443',
            $stub->stubOpts['grpc.ssl_target_name_override']
        );
        $this->assertEquals('my-channel', $stub->channel);
    }

    public function testCreateStubWithForceNew()
    {
        $grpcTransportImpl = new MockGrpcTransport([
            'serviceAddress' => 'my-service-address',
            'port' => 8443,
            'scopes' => $this->defaultScope,
            'forceNewChannel' => true
        ]);
        $stub = $grpcTransportImpl->getGrpcStub();
        $this->assertEquals('my-service-address:8443', $stub->hostname);
        $this->assertEquals('DummySslCreds', $stub->stubOpts['credentials']);
        $this->assertEquals(
            'my-service-address:8443',
            $stub->stubOpts['grpc.ssl_target_name_override']
        );
        $this->assertNull($stub->channel);
        $this->assertTrue($stub->stubOpts['force_new']);
    }
}

class MockGrpcTransport
{
    use GrpcTransportTrait;

    private static $grpcStubClassName = 'Google\GAX\UnitTests\MockGrpcTransportStub';

    /**
     * Get the generated Grpc Stub
     */
    public function getGrpcStub()
    {
        return $this->grpcStub;
    }

    /**
     * Test constructGrpcArgs function
     */
    public function doConstructGrpcArgs($optionalArgs = [])
    {
        return $this->constructGrpcArgs($optionalArgs);
    }

    protected function getADCCredentials($scopes)
    {
        return new MockCredentialsLoader($scopes, [
            [
                'access_token' => 'adcAccessToken',
                'expires_in' => '100',
            ],
        ]);
    }

    protected function createSslChannelCredentials()
    {
        return "DummySslCreds";
    }
}

class MockGrpcTransportStub
{
    public $hostname;
    public $stubOpts;
    public $channel;

    public function __construct($hostname, $stubOpts, $channel)
    {
        $this->hostname = $hostname;
        $this->stubOpts = $stubOpts;
        $this->channel = $channel;
    }
}
