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
use Google\ApiCore\RetrySettings;
use Google\ApiCore\Testing\MockRequest;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\ValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase;

class GapicClientTraitTest extends TestCase
{
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
        $transport->expects($this->once())
             ->method('startUnaryCall')
             ->with(
                $this->isInstanceOf(Call::class),
                $this->equalTo([
                    'headers' => $expectedHeaders,
                    'authWrapper' => null,
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
        $expectedPromise = $this->getMock(PromiseInterface::class);
        $transport = $this->getMock(TransportInterface::class);
        $transport->expects($this->once())
             ->method('startUnaryCall')
             ->will($this->returnValue($expectedPromise));
        $client = new GapicClientTraitStub();
        $client->set('transport', $transport);
        $client->set('agentHeaderDescriptor', $agentHeaderDescriptor);
        $client->set('retrySettings', ['method' => $retrySettings]);
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

    public function testSetServiceNameAndDescriptors()
    {
        $serviceName = 'test.interface.v1.api';
        $descriptorsFile = __DIR__ . '/testdata/test_service_descriptor_config.php';
        $options = [
            'serviceName' => $serviceName,
            'descriptorsConfigPath' => $descriptorsFile,
        ];

        $client = new GapicClientTraitStub();
        $client->call('setServiceNameAndDescriptors', [
            $options
        ]);

        $this->assertSame($serviceName, $client->get('serviceName'));
        $expectedDescriptors = require $descriptorsFile;
        $this->assertSame($expectedDescriptors['interfaces'][$serviceName], $client->get('descriptors'));
    }

    /**
     * @dataProvider setRetrySettingsDataProvider
     */
    public function testSetRetrySettings($options, $expectedRetrySettings)
    {
        $client = new GapicClientTraitStub();
        $client->call('setRetrySettings', [
            $options
        ]);

        $this->assertEquals($expectedRetrySettings, $client->get('retrySettings'));
    }

    public function setRetrySettingsDataProvider()
    {
        $serviceName = 'test.interface.v1.api';
        $clientConfigFile = __DIR__ . '/testdata/test_service_client_config.json';

        $manualClientConfig = [
            "interfaces" => [
                "test.interface.v1.api" => [
                    "retry_codes" => [
                        "idempotent" => [
                            "DEADLINE_EXCEEDED",
                            "UNAVAILABLE"
                        ]
                    ],
                    "retry_params" => [
                        "default" => [
                            "initial_retry_delay_millis" => 100,
                            "retry_delay_multiplier" => 1.2,
                            "max_retry_delay_millis" => 1000,
                            "initial_rpc_timeout_millis" => 300,
                            "rpc_timeout_multiplier" => 1.3,
                            "max_rpc_timeout_millis" => 3000,
                            "total_timeout_millis" => 30000,
                        ]
                    ],
                    "methods" => [
                        "ManualConfigMethod" => [
                            "timeout_millis" => 40000,
                            "retry_codes_name" => "idempotent",
                            "retry_params_name" => "default"
                        ]
                    ]
                ]
            ]
        ];

        $retrySettingsFromFile = RetrySettings::load($serviceName, json_decode(file_get_contents($clientConfigFile), true), []);
        $manualLoadedRetrySettings = RetrySettings::load($serviceName, $manualClientConfig, []);
        $manualLoadedRetrySettingsDisabled = [];
        foreach ($manualLoadedRetrySettings as $method => $retrySetting) {
            $manualLoadedRetrySettingsDisabled[$method] = $retrySetting->with([
                'retriesEnabled' => false
            ]);
        }

        return [
            [
                [
                    'serviceName' => $serviceName,
                    'clientConfig' => $clientConfigFile,
                    'disableRetries' => false,
                ],
                $retrySettingsFromFile,
            ],
            [
                [
                    'serviceName' => $serviceName,
                    'clientConfig' => $manualClientConfig,
                    'disableRetries' => false,
                ],
                $manualLoadedRetrySettings,
            ],
            [
                [
                    'serviceName' => $serviceName,
                    'clientConfig' => $manualClientConfig,
                    'disableRetries' => true,
                ],
                $manualLoadedRetrySettingsDisabled,
            ],
        ];
    }

    /**
     * @dataProvider setAgentHeaderDescriptorData
     */
    public function testSetAgentHeaderDescriptor($options, $expectedHeaderContent)
    {
        $client = new GapicClientTraitStub();
        $client->call('setAgentHeaderDescriptor', [
            $options
        ]);

        $agentHeaderDescriptor = $client->get('agentHeaderDescriptor');
        $actualHeader = $agentHeaderDescriptor->getHeader();
        $actualHeaderContent = $actualHeader[AgentHeaderDescriptor::AGENT_HEADER_KEY];
        $this->assertEquals($expectedHeaderContent, $actualHeaderContent);
    }

    public function setAgentHeaderDescriptorData()
    {
        $phpVersion = phpversion();
        $apiCoreVersion = AgentHeaderDescriptor::API_CORE_VERSION;
        $grpcVersion = phpversion('grpc');
        return [
            [[], ["gl-php/$phpVersion gapic/ gax/$apiCoreVersion grpc/$grpcVersion"]],
            [[
                'libName' => 'testLibName',
                'libVersion' => 'testLibVersion',
                'gapicVersion' => 'testGapicVersion',
            ], ["gl-php/$phpVersion testLibName/testLibVersion gapic/testGapicVersion gax/$apiCoreVersion grpc/$grpcVersion"]],
        ];
    }

    /**
     * @dataProvider setTransportData
     */
    public function testSetTransport($options, $expectedTransportClass)
    {
        $client = new GapicClientTraitStub();
        $client->call('setTransport', [
            $options
        ]);

        $transport = $client->get('transport');
        $this->assertEquals($expectedTransportClass, get_class($transport));
    }

    public function setTransportData()
    {
        $mockTransport = $this->prophesize(TransportInterface::class)->reveal();
        $defaultTransportClass = extension_loaded('grpc')
            ? GrpcTransport::class
            : RestTransport::class;
        $minimalOptions = [
            'serviceAddress' => 'address:443',
            'transport' => null,
            'transportConfig' => [
                'rest' => [
                    'restConfigPath' => __DIR__ . '/testdata/test_service_rest_client_config.php',
                ],
            ],
        ];
        return [
            [$minimalOptions, $defaultTransportClass],
            [['transport' => 'grpc'] + $minimalOptions, GrpcTransport::class],
            [['transport' => 'rest'] + $minimalOptions, RestTransport::class],
            [['transport' => $mockTransport] + $minimalOptions, get_class($mockTransport)],
        ];
    }

    /**
     * @dataProvider setTransportDataInvalid
     * @expectedException \Google\ApiCore\ValidationException
     */
    public function testSetTransportInvalid($options)
    {
        $client = new GapicClientTraitStub();
        $client->call('setTransport', [
            $options
        ]);
    }

    public function setTransportDataInvalid()
    {
        $minimalOptions = [
            'serviceAddress' => 'address:443',
            'transport' => null,
            'transportConfig' => null,
        ];
        return [
            [[]],
            [['transport' => 'weirdstring'] + $minimalOptions],
            [['transport' => new \stdClass()] + $minimalOptions],
        ];
    }
}

class GapicClientTraitStub
{
    use GapicClientTrait;

    private static function getClientDefaults()
    {
        return [
            'serviceAddress' => 'test.address.com:443',
            'clientConfig' => __DIR__ . '/testdata/test_service_rest_client_config.php',
            'disableRetries' => false,

        ];
    }

    public function call($fn, array $args = [])
    {
        return call_user_func_array([$this, $fn], $args);
    }

    public function set($name, $val, $static = false)
    {
        if ($static) {
            $this::$$name = $val;
        } else {
            $this->$name = $val;
        }
    }

    public function get($name)
    {
        return $this->$name;
    }
}
