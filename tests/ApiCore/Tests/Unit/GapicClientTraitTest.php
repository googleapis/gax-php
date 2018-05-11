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
use Google\ApiCore\BidiStream;
use Google\ApiCore\ClientStream;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Call;
use Google\ApiCore\GapicClientTrait;
use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\ServerStream;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\ValidationException;
use Google\Auth\FetchAuthTokenInterface;
use Google\LongRunning\Operation;
use GPBMetadata\Google\Api\Auth;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GapicClientTraitTest extends TestCase
{
    use TestTrait;

    public function tearDown()
    {
        // Reset the static gapicVersion field between tests
        $client = new GapicClientTraitStub();
        $client->set('gapicVersion', null, true);
    }

    public function testHeadersOverwriteBehavior()
    {
        $headerDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => '0.0.0',
            'gapicVersion' => '0.9.0',
            'apiCoreVersion' => '1.0.0',
            'phpVersion' => '5.5.0',
            'grpcVersion' => '1.0.1'
        ]);
        $headers = [
            'x-goog-api-client' => ['this-should-not-be-used'],
            'new-header' => ['this-should-be-used']
        ];
        $expectedHeaders = [
            'x-goog-api-client' => ['gl-php/5.5.0 gccl/0.0.0 gapic/0.9.0 gax/1.0.0 grpc/1.0.1'],
            'new-header' => ['this-should-be-used'],
        ];
        $transport = $this->getMock(TransportInterface::class);
        $credentialsWrapper = CredentialsWrapper::build([]);
        $transport->expects($this->once())
            ->method('startUnaryCall')
            ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'headers' => $expectedHeaders,
                    'credentialsWrapper' => $credentialsWrapper,
                ])
            );
        $client = new GapicClientTraitStub();
        $client->set('agentHeaderDescriptor', $headerDescriptor);
        $client->set('retrySettings', [
            'method' => $this->getMockBuilder(RetrySettings::class)
                ->disableOriginalConstructor()
                ->getMock()
            ]
        );
        $client->set('transport', $transport);
        $client->set('credentialsWrapper', $credentialsWrapper);
        $client->call('startCall', [
            'method',
            'decodeType',
            ['headers' => $headers]
        ]);
    }

    public function testStartOperationsCall()
    {
        $agentHeaderDescriptor = $this->getMockBuilder(AgentHeaderDescriptor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $agentHeaderDescriptor->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue([]));
        $retrySettings = $this->getMockBuilder(RetrySettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $longRunningDescriptors = [
            'longRunning' => [
                'operationReturnType' => 'operationType',
                'metadataReturnType' => 'metadataType',
            ]
        ];
        $expectedPromise = $this->getMock(PromiseInterface::class);
        $transport = $this->getMock(TransportInterface::class);
        $transport->expects($this->once())
             ->method('startUnaryCall')
             ->will($this->returnValue($expectedPromise));
        $credentialsWrapper = CredentialsWrapper::build([]);
        $client = new GapicClientTraitStub();
        $client->set('transport', $transport);
        $client->set('credentialsWrapper', $credentialsWrapper);
        $client->set('agentHeaderDescriptor', $agentHeaderDescriptor);
        $client->set('retrySettings', ['method' => $retrySettings]);
        $client->set('descriptors', ['method' => $longRunningDescriptors]);
        $message = new MockRequest();
        $operationsClient = $this->getMockBuilder(OperationsClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->call('startOperationsCall', [
            'method',
            [],
            $message,
            $operationsClient
        ]);
    }

    public function testGetGapicVersionWithVersionFile()
    {
        $version = '1.2.3-dev';
        $tmpFile = sys_get_temp_dir() . '/VERSION';
        file_put_contents($tmpFile, $version);
        $client = new GapicClientTraitStub();
        $client->set('gapicVersion', $version, true);
        $options = ['versionFile' => $tmpFile];
        $this->assertEquals($version, $client->call('getGapicVersion', [
            $options
        ]));
    }

    public function testGetGapicVersionWithLibVersion()
    {
        $version = '1.2.3-dev';
        $client = new GapicClientTraitStub();
        $client->set('gapicVersion', $version, true);
        $options = ['libVersion' => $version];
        $this->assertEquals($version, $client->call('getGapicVersion', [
            $options
        ]));
    }

    /**
     * @dataProvider createCredentialsWrapperData
     */
    public function testCreateCredentialsWrapper($auth, $authConfig, $expectedCredentialsWrapper)
    {
        $client = new GapicClientTraitStub();
        $actualCredentialsWrapper = $client->call('createCredentialsWrapper', [
            $auth,
            $authConfig,
        ]);

        $this->assertEquals($expectedCredentialsWrapper, $actualCredentialsWrapper);
    }

    public function createCredentialsWrapperData()
    {
        $keyFilePath = __DIR__ . '/testdata/json-key-file.json';
        $keyFile = json_decode(file_get_contents($keyFilePath), true);
        $fetcher = $this->prophesize(FetchAuthTokenInterface::class)->reveal();
        $credentialsWrapper = new CredentialsWrapper($fetcher);
        return [
            [null, [], CredentialsWrapper::build()],
            [$keyFilePath, [], CredentialsWrapper::build(['keyFile' => $keyFile])],
            [$keyFile, [], CredentialsWrapper::build(['keyFile' => $keyFile])],
            [$fetcher, [], new CredentialsWrapper($fetcher)],
            [$credentialsWrapper, [], $credentialsWrapper],
        ];
    }

    /**
     * @dataProvider createCredentialsWrapperValidationExceptionData
     * @expectedException \Google\ApiCore\ValidationException
     */
    public function testCreateCredentialsWrapperValidationException($auth, $authConfig)
    {
        $client = new GapicClientTraitStub();
        $client->call('createCredentialsWrapper', [
            $auth,
            $authConfig,
        ]);
    }

    public function createCredentialsWrapperValidationExceptionData()
    {
        return [
            ['not a json string', []],
            [new \stdClass(), []],
        ];
    }

    /**
     * @dataProvider createCredentialsWrapperInvalidArgumentExceptionData
     * @expectedException \InvalidArgumentException
     */
    public function testCreateCredentialsWrapperInvalidArgumentException($auth, $authConfig)
    {
        $client = new GapicClientTraitStub();
        $client->call('createCredentialsWrapper', [
            $auth,
            $authConfig,
        ]);
    }

    public function createCredentialsWrapperInvalidArgumentExceptionData()
    {
        return [
            [['array' => 'without right keys'], []],
        ];
    }

    /**
     * @dataProvider createTransportData
     */
    public function testCreateTransport($serviceAddress, $transport, $transportConfig, $expectedTransportClass)
    {
        if ($expectedTransportClass == GrpcTransport::class) {
            $this->requiresGrpcExtension();
        }
        $client = new GapicClientTraitStub();
        $transport = $client->call('createTransport', [
            $serviceAddress,
            $transport,
            $transportConfig
        ]);

        $this->assertEquals($expectedTransportClass, get_class($transport));
    }

    public function createTransportData()
    {
        $defaultTransportClass = extension_loaded('grpc')
            ? GrpcTransport::class
            : RestTransport::class;
        $serviceAddress = 'address:443';
        $transport = extension_loaded('grpc')
            ? 'grpc'
            : 'rest';
        $transportConfig = [
            'rest' => [
                'restClientConfigPath' => __DIR__ . '/testdata/test_service_rest_client_config.php',
            ],
        ];
        return [
            [$serviceAddress, $transport, $transportConfig, $defaultTransportClass],
            [$serviceAddress, 'grpc', $transportConfig, GrpcTransport::class],
            [$serviceAddress, 'rest', $transportConfig, RestTransport::class],
        ];
    }

    /**
     * @dataProvider createTransportDataInvalid
     * @expectedException \Google\ApiCore\ValidationException
     */
    public function testCreateTransportInvalid($serviceAddress, $transport, $transportConfig)
    {
        $client = new GapicClientTraitStub();
        $client->call('createTransport', [
            $serviceAddress,
            $transport,
            $transportConfig
        ]);
    }

    public function createTransportDataInvalid()
    {
        $serviceAddress = 'address:443';
        $transportConfig = [
            'rest' => [
                'restConfigPath' => __DIR__ . '/testdata/test_service_rest_client_config.php',
            ],
        ];
        return [
            [$serviceAddress, null, $transportConfig],
            [$serviceAddress, ['transport' => 'weirdstring'], $transportConfig],
            [$serviceAddress, ['transport' => new \stdClass()], $transportConfig],
            [$serviceAddress, ['transport' => 'rest'], []],
        ];
    }

    /**
     * @dataProvider setClientOptionsData
     */
    public function testSetClientOptions($options, $expectedProperties)
    {
        $client = new GapicClientTraitStub();
        $updatedOptions = $client->call('buildClientOptions', [$options]);
        $client->call('setClientOptions', [$updatedOptions]);
        foreach ($expectedProperties as $propertyName => $expectedValue) {
            $actualValue = $client->get($propertyName);
            $this->assertEquals($expectedValue, $actualValue);
        }
    }

    public function setClientOptionsData()
    {
        $clientDefaults = GapicClientTraitStub::getClientDefaults();
        $expectedRetrySettings = RetrySettings::load(
            $clientDefaults['serviceName'],
            json_decode(file_get_contents($clientDefaults['clientConfig']), true),
            []
        );
        $disabledRetrySettings = [];
        foreach ($expectedRetrySettings as $method => $retrySettingsItem) {
            $disabledRetrySettings[$method] = $retrySettingsItem->with([
                'retriesEnabled' => false
            ]);
        }
        $expectedProperties = [
            'serviceName' => 'test.interface.v1.api',
            'agentHeaderDescriptor' => new AgentHeaderDescriptor([]),
            'retrySettings' => $expectedRetrySettings,
        ];
        return [
            [[], $expectedProperties],
            [['disableRetries' => true], ['retrySettings' => $disabledRetrySettings] + $expectedProperties],
        ];
    }

    /**
     * @dataProvider buildClientOptionsProvider
     */
    public function testBuildClientOptions($options, $expectedUpdatedOptions)
    {
        $client = new GapicClientTraitStub();
        $updatedOptions = $client->call('buildClientOptions', [$options]);
        $this->assertEquals($expectedUpdatedOptions, $updatedOptions);
    }

    public function buildClientOptionsProvider()
    {
        $defaultOptions = [
            'serviceAddress' => 'test.address.com:443',
            'serviceName' => 'test.interface.v1.api',
            'clientConfig' => __DIR__ . '/testdata/test_service_client_config.json',
            'descriptorsConfigPath' => __DIR__.'/testdata/test_service_descriptor_config.php',
            'disableRetries' => false,
            'auth' => null,
            'authConfig' => null,
            'transport' => null,
            'transportConfig' => [
                'grpc' => [],
                'rest' => [
                    'restClientConfigPath' => __DIR__.'/testdata/test_service_rest_client_config.php',
                ]
            ],
            'credentials' => null,
            'credentialsConfig' => [],
            'gapicVersion' => null,
            'libName' => null,
            'libVersion' => null,
        ];

        $restConfigOptions = $defaultOptions;
        $restConfigOptions['transportConfig']['rest'] = [
            'restClientConfigPath' => __DIR__.'/testdata/test_service_rest_client_config.php',
            'customRestConfig' => 'value'
        ];
        $grpcConfigOptions = $defaultOptions;
        $grpcConfigOptions['transportConfig']['grpc'] = [
            'customGrpcConfig' => 'value'
        ];
        return [
            [[], $defaultOptions],
            [
                [
                    'transportConfig' => [
                        'rest' => [
                            'customRestConfig' => 'value'
                        ]
                    ]
                ], $restConfigOptions
            ],
            [
                [
                    'transportConfig' => [
                        'grpc' => [
                            'customGrpcConfig' => 'value'
                        ]
                    ]
                ], $grpcConfigOptions
            ],
        ];
    }

    public function testModifyClientOptions()
    {
        $options = [];
        $client = new GapicClientTraitStubExtension();
        $updatedOptions = $client->call('buildClientOptions', [$options]);

        $this->assertArrayHasKey('addNewOption', $updatedOptions);
        $this->assertSame(true, $updatedOptions['disableRetries']);
    }

    private function buildClientToTestModifyCallMethods()
    {
        $agentHeaderDescriptor = $this->getMockBuilder(AgentHeaderDescriptor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $agentHeaderDescriptor->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue([]));
        $retrySettings = $this->getMockBuilder(RetrySettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $longRunningDescriptors = [
            'longRunning' => [
                'operationReturnType' => 'operationType',
                'metadataReturnType' => 'metadataType',
            ]
        ];
        $pageStreamingDescriptors = [
            'pageStreaming' => [
                'requestPageTokenGetMethod' => 'getPageToken',
                'requestPageTokenSetMethod' => 'setPageToken',
                'requestPageSizeGetMethod' => 'getPageSize',
                'requestPageSizeSetMethod' => 'setPageSize',
                'responsePageTokenGetMethod' => 'getNextPageToken',
                'resourcesGetMethod' => 'getResources',
            ],
        ];
        $transport = $this->getMock(TransportInterface::class);
        $credentialsWrapper = CredentialsWrapper::build([]);
        $client = new GapicClientTraitStubExtension();
        $client->set('transport', $transport);
        $client->set('credentialsWrapper', $credentialsWrapper);
        $client->set('agentHeaderDescriptor', $agentHeaderDescriptor);
        $client->set('retrySettings', [
            'simpleMethod' => $retrySettings,
            'longRunningMethod' => $retrySettings,
            'pagedMethod' => $retrySettings,
            'bidiStreamingMethod' => $retrySettings,
            'clientStreamingMethod' => $retrySettings,
            'serverStreamingMethod' => $retrySettings,
        ]);
        $client->set('descriptors', [
            'longRunningMethod' => $longRunningDescriptors,
            'pagedMethod' => $pageStreamingDescriptors,
        ]);
        return [$client, $transport];
    }

    public function testModifyUnaryCallFromStartCall()
    {
        list($client, $transport) = $this->buildClientToTestModifyCallMethods();
        $transport->expects($this->once())
            ->method('startUnaryCall')
            ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'transportOptions' => [
                        'custom' => ['addModifyUnaryCallableOption' => true]
                    ],
                    'headers' => [],
                    'credentialsWrapper' => CredentialsWrapper::build([])
                ])
            )
            ->willReturn(new FulfilledPromise(new Operation()));
        $client->call('startCall', [
            'simpleMethod',
            'decodeType',
            [],
            new MockRequest(),
        ])->wait();
    }

    public function testModifyUnaryCallFromOperationsCall()
    {
        list($client, $transport) = $this->buildClientToTestModifyCallMethods();
        $transport->expects($this->once())
            ->method('startUnaryCall')
            ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'transportOptions' => [
                        'custom' => ['addModifyUnaryCallableOption' => true]
                    ],
                    'headers' => [],
                    'credentialsWrapper' => CredentialsWrapper::build([])
                ])
            )
            ->willReturn(new FulfilledPromise(new Operation()));
        $operationsClient = $this->getMockBuilder(OperationsClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->call('startOperationsCall', [
            'longRunningMethod',
            [],
            new MockRequest(),
            $operationsClient
        ])->wait();
    }

    public function testModifyUnaryCallFromGetPagedListResponse()
    {
        list($client, $transport) = $this->buildClientToTestModifyCallMethods();
        $transport->expects($this->once())
            ->method('startUnaryCall')
            ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'transportOptions' => [
                        'custom' => ['addModifyUnaryCallableOption' => true]
                    ],
                    'headers' => [],
                    'credentialsWrapper' => CredentialsWrapper::build([])
                ])
            )
            ->willReturn(new FulfilledPromise(new Operation()));
        $client->call('getPagedListResponse', [
            'pagedMethod',
            [],
            'decodeType',
            new MockRequest(),
        ]);
    }

    /**
     * @dataProvider modifyStreamingCallFromStartCallData
     */
    public function testModifyStreamingCallFromStartCall($callArgs, $expectedMethod, $expectedResponse)
    {
        list($client, $transport) = $this->buildClientToTestModifyCallMethods();
        $transport->expects($this->once())
            ->method($expectedMethod)
            ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'transportOptions' => [
                        'custom' => ['addModifyStreamingCallable' => true]
                    ],
                    'headers' => [],
                    'credentialsWrapper' => CredentialsWrapper::build([])
                ])
            )
            ->willReturn($expectedResponse);
        $client->call('startCall', $callArgs);
    }

    public function modifyStreamingCallFromStartCallData()
    {
        return [
            [
                [
                    'bidiStreamingMethod',
                    '',
                    [],
                    null,
                    Call::BIDI_STREAMING_CALL
                ],
                'startBidiStreamingCall',
                $this->getMockBuilder(BidiStream::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            ],
            [
                [
                    'clientStreamingMethod',
                    '',
                    [],
                    null,
                    Call::CLIENT_STREAMING_CALL
                ],
                'startClientStreamingCall',
                $this->getMockBuilder(ClientStream::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            ],
            [
                [
                    'serverStreamingMethod',
                    '',
                    [],
                    new MockRequest(),
                    Call::SERVER_STREAMING_CALL
                ],
                'startServerStreamingCall',
                $this->getMockBuilder(ServerStream::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            ],
        ];
    }
}

class GapicClientTraitStub
{
    use GapicClientTrait;

    public static function getClientDefaults()
    {
        return [
            'serviceAddress' => 'test.address.com:443',
            'serviceName' => 'test.interface.v1.api',
            'clientConfig' => __DIR__ . '/testdata/test_service_client_config.json',
            'descriptorsConfigPath' => __DIR__.'/testdata/test_service_descriptor_config.php',
            'disableRetries' => false,
            'auth' => null,
            'authConfig' => null,
            'transport' => null,
            'transportConfig' => [
                'rest' => [
                    'restClientConfigPath' => __DIR__.'/testdata/test_service_rest_client_config.php',
                ]
            ],
        ];
    }

    public function call($fn, array $args = [])
    {
        return call_user_func_array([$this, $fn], $args);
    }

    public function set($name, $val, $static = false)
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException("Property not found: $name");
        }
        if ($static) {
            $this::$$name = $val;
        } else {
            $this->$name = $val;
        }
    }

    public function get($name)
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException("Property not found: $name");
        }
        return $this->$name;
    }
}

class GapicClientTraitStubExtension extends GapicClientTraitStub
{
    protected function modifyClientOptions(array &$options)
    {
        $options['disableRetries'] = true;
        $options['addNewOption'] = true;
    }

    protected function modifyUnaryCallable(callable &$callable)
    {
        $originalCallable = $callable;
        $callable = function ($call, $options) use ($originalCallable) {
            $options['transportOptions'] = [
                'custom' => ['addModifyUnaryCallableOption' => true]
            ];
            return $originalCallable($call, $options);
        };
    }

    protected function modifyStreamingCallable(callable &$callable)
    {
        $originalCallable = $callable;
        $callable = function ($call, $options) use ($originalCallable) {
            $options['transportOptions'] = [
                'custom' => ['addModifyStreamingCallable' => true]
            ];
            return $originalCallable($call, $options);
        };
    }
}
