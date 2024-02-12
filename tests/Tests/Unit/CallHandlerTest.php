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
use Google\ApiCore\Middleware\MiddlewareInterface;
use Google\ApiCore\LongRunning\OperationsClient;
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

class CallHandlerTest extends TestCase
{
    use ProphecyTrait;

    private static array $basicDescriptor = [
        'callType' => Call::UNARY_CALL,
        'responseType' => 'decodeType'
    ];

    private function mockTransport(): TransportInterface
    {
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->then(Argument::cetera())
            ->willReturn($promise->reveal());
        $promise->wait()
            ->willReturn(null);

        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(Argument::type(Call::class), Argument::type('array'))
            ->willReturn($promise->reveal());

        return $transport->reveal();
    }

    public function testHeadersOverwriteBehavior()
    {
        $headerParam = [
            'fieldAccessors' => ['getName'],
            'keyName' => 'name'
        ];
        $methodDescriptor = self::$basicDescriptor + ['headerParams' => [$headerParam]];
        $request = new MockRequestBody(['name' => 'foos/123/bars/456']);

        $customHeaders = [
            'x-goog-api-client' => ['this-should-not-be-used'],
            'new-header' => ['this-should-be-used']
        ];
        $agentHeader = AgentHeader::buildAgentHeader([]);
        $expectedHeaders = $agentHeader + [
            'new-header' => ['this-should-be-used'],
            'x-goog-request-params' => ['name=foos%2F123%2Fbars%2F456']
        ];

        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(Argument::type(Call::class), [
                'headers' => $expectedHeaders,
                'timeoutMillis' => 30000,
                'credentialsWrapper' => $credentialsWrapper->reveal(),
            ])
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(PromiseInterface::class)->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal(),
            agentHeader: $agentHeader,
        );
        $callHandler->startApiCall('Method', $request, ['headers' => $customHeaders]);
    }

    public function testOptionalArgsAcceptsRetrySettingsArray()
    {
        $retrySettings = $this->prophesize(RetrySettings::class);
        $retrySettings->retriesEnabled()->willReturn(true);
        $retrySettings->getInitialRpcTimeoutMillis()->willReturn(0);
        $retrySettings->with(['rpcTimeoutMultiplier' => 5])
            ->shouldBeCalledOnce()
            ->willReturn($retrySettings->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $this->mockTransport(),
            ['Method' => $retrySettings->reveal()],
            [],
            null,
        );
        $callHandler->startApiCall(
            'Method',
            null,
            ['retrySettings' => ['rpcTimeoutMultiplier' => 5]]
        );
    }

    public function testOptionalArgsAcceptsRetrySettingsObject()
    {
        $retrySettings = $this->prophesize(RetrySettings::class);
        $retrySettings->retriesEnabled()
            ->willReturn(true)
            ->shouldBeCalledTimes(2);
        $retrySettings->getInitialRpcTimeoutMillis()
            ->willReturn(0)
            ->shouldBeCalledOnce();

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $this->mockTransport(),
            ['Method' => []], // this will be ignored
        );
        $callHandler->startApiCall('Method', null, ['retrySettings' => $retrySettings->reveal()]);
    }

    public function testStartApiCallOperation()
    {
        $longRunningDescriptor = [
            'operationReturnType' => 'operationType',
            'metadataReturnType' => 'metadataType',
            'initialPollDelayMillis' => 100,
            'pollDelayMultiplier' => 1.0,
            'maxPollDelayMillis' => 200,
            'totalPollTimeoutMillis' => 300,
        ];

        $methodDescriptor = [
            'callType' => Call::LONGRUNNING_CALL,
            'longRunning' => $longRunningDescriptor,
        ];

        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(Argument::type(Call::class), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn(new FulfilledPromise(new Operation()));

        $operationsClient = $this->prophesize(ClientInterface::class);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal(),
            operationsClient: $operationsClient->reveal()
        );

        $response = $callHandler->startApiCall('Method', new MockRequest())->wait();

        $expectedResponse = new OperationResponse(
            '',
            $operationsClient->reveal(),
            $longRunningDescriptor + ['lastProtoResponse' => new Operation()]
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testStartApiCallCustomOperation()
    {
        $longRunningDescriptor = [
            'operationReturnType' => 'operationType',
            'metadataReturnType' => 'metadataType',
            'initialPollDelayMillis' => 100,
            'pollDelayMultiplier' => 1.0,
            'maxPollDelayMillis' => 200,
            'totalPollTimeoutMillis' => 300,
        ];

        $methodDescriptor = [
            'callType' => Call::LONGRUNNING_CALL,
            'responseType' => MockResponse::class,
            'longRunning' => $longRunningDescriptor,
        ];

        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(Argument::type(Call::class), Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn(new FulfilledPromise(new MockResponse()));

        $operationsClient = $this->prophesize(ClientInterface::class);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal(),
            operationsClient: $operationsClient->reveal()
        );

        $response = $callHandler->startApiCall('Method', new MockRequest())->wait();

        $expectedResponse = new OperationResponse(
            '',
            $operationsClient->reveal(),
            $longRunningDescriptor + ['lastProtoResponse' => new MockResponse()]
        );

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @dataProvider startApiCallExceptions
     */
    public function testStartApiCallException($descriptor, $expected)
    {
        // All descriptor config checks throw Validation exceptions
        $this->expectException(ValidationException::class);
        // Check that the proper exception is being thrown for the given descriptor.
        $this->expectExceptionMessage($expected);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', $descriptor),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $this->prophesize(TransportInterface::class)->reveal()
        );

        $callHandler->startApiCall('Method', new MockRequest());
    }

    public function startApiCallExceptions()
    {
        return [
            [
                [],
                'does not exist'
            ],
            [
                [
                    'Method' => []
                ],
                'does not have a callType'
            ],
            [
                [
                    'Method' => ['callType' => Call::LONGRUNNING_CALL]
                ],
                'does not have a longRunning config'
            ],
            [
                [
                    'Method' => ['callType' => Call::LONGRUNNING_CALL, 'longRunning' => []]
                ],
                'missing required getOperationsClient'
            ],
            [
                [
                    'Method' => ['callType' => Call::UNARY_CALL]
                ],
                'does not have a responseType'
            ],
            [
                [
                    'Method' => ['callType' => Call::PAGINATED_CALL, 'responseType' => 'foo']
                ],
                'does not have a pageStreaming'
            ],
        ];
    }

    /**
     * @dataProvider startAsyncCallExceptions
     * @dataProvider startApiCallExceptions
     */
    public function testStartAsyncCallException($descriptor, $expected)
    {
        // All descriptor config checks throw Validation exceptions
        $this->expectException(ValidationException::class);
        // Check that the proper exception is being thrown for the given descriptor.
        $this->expectExceptionMessage($expected);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', $descriptor),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $this->prophesize(TransportInterface::class)->reveal()
        );

        $callHandler->startAsyncCall('Method', new MockRequest());
    }

    public function startAsyncCallExceptions()
    {
        return [
            [
                [
                    'Method' => [
                        'callType' => Call::SERVER_STREAMING_CALL,
                        'responseType' => 'Google\Longrunning\Operation'
                    ]
                ],
                'not supported for async execution'
            ],
            [
                [
                    'Method' => [
                        'callType' => Call::CLIENT_STREAMING_CALL, 'longRunning' => [],
                        'responseType' => 'Google\Longrunning\Operation'
                    ]
                ],
                'not supported for async execution'
            ],
            [
                [
                    'Method'=> [
                        'callType' => Call::BIDI_STREAMING_CALL,
                        'responseType' => 'Google\Longrunning\Operation'
                    ]
                ],
                'not supported for async execution'
            ],
        ];
    }

    public function testStartApiCallUnary()
    {
        $interfaceOverride = 'google.cloud.foo.v1.Foo';
        $methodDescriptor = [
            'callType' => Call::UNARY_CALL,
            'responseType' => 'Google\Longrunning\Operation',
            'interfaceOverride' => $interfaceOverride
        ];
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::that(fn (Call $call) => strpos($call->getMethod(), $interfaceOverride) === 0),
                Argument::type('array')
            )
            ->shouldBeCalledOnce();

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal()
        );

        $callHandler->startApiCall('Method', new MockRequest());
    }

    public function testStartApiCallPaged()
    {
        $methodDescriptor = [
            'callType' => Call::PAGINATED_CALL,
            'responseType' => ListOperationsResponse::class,
            'pageStreaming' => [
                'requestPageTokenGetMethod' => 'getPageToken',
                'requestPageTokenSetMethod' => 'setPageToken',
                'requestPageSizeGetMethod' => 'getPageSize',
                'requestPageSizeSetMethod' => 'setPageSize',
                'responsePageTokenGetMethod' => 'getNextPageToken',
                'resourcesGetMethod' => 'getOperations',
            ],
        ];
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->then(Argument::cetera())
            ->willReturn($promise->reveal());
        $promise->wait()
            ->shouldBeCalledOnce();

        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(Argument::type(Call::class), Argument::type('array'))
            ->willReturn($promise->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal()
        );

        $callHandler->startApiCall('Method', new MockRequest());
    }

    public function testStartAsyncCallPaged()
    {
        $interfaceOverride = 'google.cloud.foo.v1.Foo';
        $methodDescriptor = [
            'callType' => Call::PAGINATED_CALL,
            'responseType' => 'Google\Longrunning\ListOperationsResponse',
            'interfaceOverride' => $interfaceOverride,
            'pageStreaming' => [
                'requestPageTokenGetMethod' => 'getPageToken',
                'requestPageTokenSetMethod' => 'setPageToken',
                'requestPageSizeGetMethod' => 'getPageSize',
                'requestPageSizeSetMethod' => 'setPageSize',
                'responsePageTokenGetMethod' => 'getNextPageToken',
                'resourcesGetMethod' => 'getOperations',
            ],
        ];

        $promise = $this->prophesize(PromiseInterface::class);
        $promise->then(Argument::cetera())
            ->willReturn($promise->reveal());
        $promise->wait()
            ->shouldBeCalledOnce();

        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::that(fn (Call $call) => strpos($call->getMethod(), $interfaceOverride) === 0),
                Argument::type('array')
            )
            ->willReturn($promise->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal()
        );

        $callHandler->startAsyncCall('Method', new MockRequest())->wait();
    }

    public function testAdditionalArgumentMethods()
    {
        // Set the LRO descriptors we are testing.
        $methodDescriptors = [
            'callType' => Call::LONGRUNNING_CALL,
            'longRunning' => [
                'additionalArgumentMethods' => [
                    'getPageToken',
                    'getPageSize',
                ]
            ]
        ];
        $transport = $this->prophesize(TransportInterface::class);
        $transport->startUnaryCall(Argument::any(), Argument::any())
             ->shouldBeCalledOnce()
             ->willReturn(new FulfilledPromise(new Operation(['name' => 'test-123'])));

        // Create mock operations client to test the additional arguments from
        // the request object are used.
        $operationsClient = $this->prophesize(CustomOperationsClient::class);
        $operationsClient->getOperation('test-123', 'abc', 100)
            ->shouldBeCalledOnce();

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptors]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal(),
            operationsClient: $operationsClient->reveal()
        );

        // Create the mock request object which will have additional argument
        // methods called on it.
        $request = new MockRequest([
            'page_token' => 'abc',
            'page_size'  => 100,
        ]);

        $operationResponse = $callHandler->startApiCall('Method', $request)->wait();

        // This will invoke $operationsClient->getOperation with values from
        // the additional argument methods.
        $operationResponse->reload();
    }

    /**
     * @dataProvider buildRequestHeaderParams
     */
    public function testBuildRequestHeaders(
        array $headerParam,
        MockRequestBody $request,
        string $expectedRequestParams
    ) {
        $methodDescriptor = self::$basicDescriptor + ['headerParams' => [$headerParam]];

        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::that(
                    fn ($options) => $options['headers']['x-goog-request-params'] === [$expectedRequestParams]
                )
            )
            ->shouldBeCalledOnce();

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $transport->reveal(),
        );
        $callHandler->startApiCall('Method', $request);
    }

    public function buildRequestHeaderParams()
    {
        return [
            [
                [
                    'fieldAccessors' => ['getName'],
                    'keyName' => 'name_field'
                ],
                new MockRequestBody(['name' => 'foos/123/bars/456']),
                'name_field=foos%2F123%2Fbars%2F456'
            ],
            [
                [
                    'fieldAccessors' => ['getName'],
                    'keyName' => 'name_field'
                ],
                new MockRequestBody(null),
                // For some reason RequestParamsHeaderDescriptor creates an array
                // with an empty string if there are no headers set in it.
                ''
            ],
            [
                [
                    'fieldAccessors' => ['getNestedMessage', 'getName'],
                    'keyName' => 'name_field'
                ],
                new MockRequestBody([
                    'nested_message' => new MockRequestBody([
                        'name' => 'foos/123/bars/456'
                    ])
                ]),
                'name_field=foos%2F123%2Fbars%2F456'
            ],
            [
                [
                    'fieldAccessors' => ['getNestedMessage', 'getName'],
                    'keyName' => 'name_field'
                ],
                new MockRequestBody([]),
                ''
            ],
            [
                [
                    'fieldAccessors' => ['getNestedMessage', 'getName'],
                    'keyName' => 'name_field'
                ],
                new MockRequestBody([
                    'nested_message' => new MockRequestBody()
                ]),
                ''
            ],
        ];
    }

    public function testGetCredentialsWrapper()
    {
        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $callHandler = new CallHandler(
            $this->prophesize(ServiceDescriptor::class)->reveal(),
            $credentialsWrapper->reveal(),
            $this->prophesize(TransportInterface::class)->reveal()
        );
        $this->assertEquals($credentialsWrapper->reveal(), $callHandler->getCredentialsWrapper());
    }

    public function testUserProjectHeaderIsSetWhenProvidingQuotaProject()
    {
        $quotaProject = 'test-quota-project';
        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $credentialsWrapper->getQuotaProject()
            ->shouldBeCalledOnce()
            ->willReturn($quotaProject);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                [
                    'headers' => [
                        'X-Goog-User-Project' => [$quotaProject],
                    ],
                    'timeoutMillis' => 30000,
                    'credentialsWrapper' => $credentialsWrapper->reveal()
                ]
            )
            ->willReturn($this->prophesize(PromiseInterface::class)->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal()
        );
        $callHandler->startApiCall('Method');
    }

    public function testAudience()
    {
        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::that(fn ($options) => $options['audience'] === 'default-audience')
            )
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(PromiseInterface::class)->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal(),
            audience: 'default-audience'
        );

        $callHandler->startApiCall('Method');
    }

    public function testAudienceInOptionalArgs()
    {
        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::that(fn ($options) => $options['audience'] === 'custom-audience')
            )
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(PromiseInterface::class)->reveal());

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal(),
            audience: 'default-audience'
        );

        $callHandler->startApiCall('Method', null, ['audience' => 'custom-audience']);
    }

    public function testDefaultAudienceWithOperations()
    {
        $methodDescriptor = [
            'callType' => Call::LONGRUNNING_CALL,
            'longRunning' => [
                'operationReturnType' => 'operationType',
                'metadataReturnType' => 'metadataType',
                'initialPollDelayMillis' => 100,
                'pollDelayMultiplier' => 1.0,
                'maxPollDelayMillis' => 200,
                'totalPollTimeoutMillis' => 300,
            ]
        ];

        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::that(fn ($options) => $options['audience'] === 'default-audience')
            )
            ->shouldBeCalledOnce()
            ->willReturn(new FulfilledPromise(new Operation()));

        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::type('array')
            )
            ->willReturn(new FulfilledPromise(new Operation()));

        $operationsClient = $this->prophesize(ClientInterface::class);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal(),
            operationsClient: $operationsClient->reveal(),
            audience: 'default-audience'
        );

        // Test startOperationsCall with default audience
        $callHandler->startApiCall(
            'Method',
            new MockRequest(),
        );
    }

    public function testDefaultAudienceWithPagedList()
    {
        $methodDescriptor = [
            'callType' => Call::PAGINATED_CALL,
            'responseType' => ListOperationsResponse::class,
            'pageStreaming' => [
                'requestPageTokenGetMethod' => 'getPageToken',
                'requestPageTokenSetMethod' => 'setPageToken',
                'requestPageSizeGetMethod' => 'getPageSize',
                'requestPageSizeSetMethod' => 'setPageSize',
                'responsePageTokenGetMethod' => 'getNextPageToken',
                'resourcesGetMethod' => 'getResources',
            ],
        ];
        $credentialsWrapper = $this->prophesize(CredentialsWrapper::class);
        $transport = $this->prophesize(TransportInterface::class);
        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::that(fn ($options) => $options['audience'] === 'default-audience')
            )
            ->shouldBeCalledOnce()
            ->willReturn(new FulfilledPromise(new Operation()));

        $transport
            ->startUnaryCall(
                Argument::type(Call::class),
                Argument::type('array')
            )
            ->willReturn(new FulfilledPromise(new Operation()));

        $operationsClient = $this->prophesize(ClientInterface::class);

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => $methodDescriptor]),
            $credentialsWrapper->reveal(),
            $transport->reveal(),
            operationsClient: $operationsClient->reveal(),
            audience: 'default-audience'
        );

        // Test paginated call with default audience
        $callHandler->startApiCall('Method', new MockRequest());
    }

    public function testAddMiddlewares()
    {
        $m1Called = false;
        $m2Called = false;
        $middleware1 = function (MiddlewareInterface $handler) use (&$m1Called) {
            return new class($handler, $m1Called) implements MiddlewareInterface {
                private MiddlewareInterface $handler;
                private bool $m1Called;
                public function __construct(
                    MiddlewareInterface $handler,
                    bool &$m1Called
                ) {
                    $this->handler = $handler;
                    $this->m1Called = &$m1Called;
                }
                public function __invoke(Call $call, array $options)
                {
                    $this->m1Called = true;
                    return ($this->handler)($call, $options);
                }
            };
        };
        $middleware2 = function (MiddlewareInterface $handler) use (&$m2Called) {
            return new class($handler, $m2Called) implements MiddlewareInterface {
                private MiddlewareInterface $handler;
                private bool $m2Called;
                public function __construct(
                    MiddlewareInterface $handler,
                    bool &$m2Called
                ) {
                    $this->handler = $handler;
                    $this->m2Called = &$m2Called;
                }
                public function __invoke(Call $call, array $options)
                {
                    $this->m2Called = true;
                    return ($this->handler)($call, $options);
                }
            };
        };

        $callHandler = new CallHandler(
            new ServiceDescriptor('', ['Method' => self::$basicDescriptor]),
            $this->prophesize(CredentialsWrapper::class)->reveal(),
            $this->mockTransport()
        );

        $callHandler->addMiddleware($middleware1);
        $callHandler->addMiddleware($middleware2);
        $callHandler->startApiCall('Method', new MockRequest())->wait();

        $this->assertTrue($m1Called);
        $this->assertTrue($m2Called);
    }
}
