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
use Google\ApiCore\Call;
use Google\ApiCore\CallHandler;
use Google\ApiCore\ClientInterface;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Descriptor\ServiceDescriptor;
use Google\ApiCore\OperationResponse;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Testing\MockRequestBody;
use Google\ApiCore\Testing\MockResponse;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\ValidationException;
use Google\Longrunning\ListOperationsResponse;
use Google\LongRunning\Operation;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ClientTraitTest extends TestCase
{
    use ProphecyTrait;

    // /**
    //  * @dataProvider setClientOptionsData
    //  */
    // public function testSetClientOptions($options, $expectedProperties)
    // {
    //     $client = new StubGapicClient();
    //     $updatedOptions = $client->buildClientOptions($options);
    //     $client->setClientOptions($updatedOptions);
    //     foreach ($expectedProperties as $propertyName => $expectedValue) {
    //         $actualValue = $client->get($propertyName);
    //         $this->assertEquals($expectedValue, $actualValue);
    //     }
    // }

    // public function setClientOptionsData()
    // {
    //     $clientDefaults = StubGapicClient::getClientDefaults();
    //     $expectedRetrySettings = RetrySettings::load(
    //         $clientDefaults['serviceName'],
    //         json_decode(file_get_contents($clientDefaults['clientConfig']), true)
    //     );
    //     $disabledRetrySettings = [];
    //     foreach ($expectedRetrySettings as $method => $retrySettingsItem) {
    //         $disabledRetrySettings[$method] = $retrySettingsItem->with([
    //             'retriesEnabled' => false
    //         ]);
    //     }
    //     $expectedProperties = [
    //         'serviceName' => 'test.interface.v1.api',
    //         'agentHeader' => AgentHeader::buildAgentHeader([]) + ['User-Agent' => ['gcloud-php-legacy/']],
    //         'retrySettings' => $expectedRetrySettings,
    //     ];
    //     return [
    //         [[], $expectedProperties],
    //         [['disableRetries' => true], ['retrySettings' => $disabledRetrySettings] + $expectedProperties],
    //     ];
    // }

    // public function testDefaultAudienceIsServiceAddress()
    // {
    //     $transport = $this->prophesize(TransportInterface::class);
    //     $transport
    //         ->startUnaryCall(
    //             Argument::type(Call::class),
    //             [
    //                 'audience' => 'https://service-address/',
    //                 'headers' => [],
    //                 'credentialsWrapper' => $credentialsWrapper,
    //             ]
    //         )
    //         ->shouldBeCalledOnce()
    //         ->willReturn($this->prophesize(PromiseInterface::class)->reveal());

    //     $client = new CallHandler(
    //         new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
    //         $this->prophesize(CredentialsWrapper::class)->reveal(),
    //         $transport->reveal(),
    //         audience: 'custom-audience'
    //     );

    //     $client->startCall('method.name', 'decodeType', [
    //         'audience' => 'custom-audience',
    //     ]);
    // }

    // public function testSupportedTransportOverrideWithDefaultTransport()
    // {
    //     $client = new TransportSupportTraitRestOnlyImpl();
    //     $this->assertInstanceOf(RestTransport::class, $client->createTransport());
    // }

    // public function testSupportedTransportOverrideWithExplicitTransport()
    // {
    //     $client = new RestOnlyGapicClient(['transport' => 'rest']);
    //     $this->assertInstanceOf(RestTransport::class, $client->getTransport());
    // }

    // public function testCallOptions()
    // {
    //     list($client, $transport) = $this->buildClientToTestModifyCallMethods(
    //         GapicV2SurfaceClient::class
    //     );

    //     $transport->expects($this->once())
    //         ->method('startUnaryCall')
    //         ->with(
    //             $this->isInstanceOf(Call::class),
    //             $this->equalTo([
    //                 'headers' => AgentHeader::buildAgentHeader([]) + ['Foo' => 'Bar'],
    //                 'credentialsWrapper' => CredentialsWrapper::build([
    //                     'keyFile' => __DIR__ . '/testdata/json-key-file.json'
    //                 ]),
    //                 'timeoutMillis' => null, // adds null timeoutMillis,
    //                 'transportOptions' => [],
    //             ])
    //         )
    //         ->willReturn(new FulfilledPromise(new Operation()));

    //     $callOptions = [
    //         'headers' => ['Foo' => 'Bar'],
    //         'invalidOption' => 'wont-be-passed'
    //     ];
    //     $client->startCall(
    //         'simpleMethod',
    //         'decodeType',
    //         $callOptions,
    //         new MockRequest(),
    //     )->wait();
    // }

    // public function testInvalidCallOptionsTypeForV2SurfaceThrowsException()
    // {
    //     $this->expectException(\TypeError::class);
    //     $this->expectExceptionMessage(
    //         PHP_MAJOR_VERSION < 8
    //             ? 'Argument 1 passed to Google\ApiCore\Options\CallOptions::setTimeoutMillis() '
    //                 . 'must be of the type int or null, string given'
    //             : 'Google\ApiCore\Options\CallOptions::setTimeoutMillis(): Argument #1 '
    //                 . '($timeoutMillis) must be of type ?int, string given'
    //     );

    //     list($client, $_) = $this->buildClientToTestModifyCallMethods(GapicV2SurfaceClient::class);

    //     $client->startCall(
    //         'simpleMethod',
    //         'decodeType',
    //         ['timeoutMillis' => 'blue'], // invalid type, will throw exception
    //         new MockRequest(),
    //     )->wait();
    // }

    // public function testSurfaceAgentHeaders()
    // {
    //     // V2 contains new headers
    //     $client = new GapicV2SurfaceClient([
    //         'gapicVersion' => '0.0.1',
    //     ]);
    //     $agentHeader = $client->getAgentHeader();
    //     $this->assertStringContainsString(' gapic/0.0.1 ', $agentHeader['x-goog-api-client'][0]);
    //     $this->assertEquals('gcloud-php-new/0.0.1', $agentHeader['User-Agent'][0]);
    // }
}
