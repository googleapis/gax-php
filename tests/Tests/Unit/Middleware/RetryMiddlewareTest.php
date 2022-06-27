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

namespace Google\ApiCore\Tests\Unit\Middleware;

use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
use Google\ApiCore\Call;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\ApiCore\RetrySettings;
use Google\Rpc\Code;
use GuzzleHttp\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectException;

class RetryMiddlewareTest extends TestCase
{
    use ExpectException;

    public function testRetryNoRetryableCode()
    {
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => false,
                'retryableCodes' => []
            ]);
        $callCount = 0;
        $handler = function(Call $call, $options) use (&$callCount) {
            return new Promise(function () use (&$callCount) {
                throw new ApiException('Call Count: ' . $callCount += 1, 0, '');
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Call Count: 1');

        $middleware($call, [])->wait();
    }

    public function testRetryBackoff()
    {
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => true,
                'retryableCodes' => [ApiStatus::CANCELLED],
            ]);
        $callCount = 0;
        $handler = function(Call $call, $options) use (&$callCount) {
            $callCount += 1;
            return $promise = new Promise(function () use (&$promise, $callCount) {
                if ($callCount < 3) {
                    throw new ApiException('Cancelled!', Code::CANCELLED, ApiStatus::CANCELLED);
                }
                $promise->resolve('Ok!');
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);
        $response = $middleware(
            $call,
            []
        )->wait();

        $this->assertSame('Ok!', $response);
        $this->assertEquals(3, $callCount);
    }

    public function testRetryTimeoutExceedsMaxTimeout()
    {
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => true,
                'retryableCodes' => [ApiStatus::CANCELLED],
                'totalTimeoutMillis' => 0,
            ]);
        $handler = function(Call $call, $options) {
            return new Promise(function () {
                throw new ApiException('Cancelled!', Code::CANCELLED, ApiStatus::CANCELLED);
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Retry total timeout exceeded.');

        $middleware($call, [])->wait();
    }

    public function testRetryTimeoutExceedsRealTime()
    {
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => true,
                'retryableCodes' => [ApiStatus::CANCELLED],
                'initialRpcTimeoutMillis' => 500,
                'totalTimeoutMillis' => 1000,
            ]);
        $handler = function(Call $call, $options) {
            return new Promise(function () use ($options) {
                // sleep for the duration of the timeout
                if (isset($options['timeoutMillis'])) {
                    usleep(intval($options['timeoutMillis'] * 1000));
                }
                throw new ApiException('Cancelled!', Code::CANCELLED, ApiStatus::CANCELLED);
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Retry total timeout exceeded.');

        $middleware($call, [])->wait();
    }

    public function testTimeoutMillisCallSettingsOverwrite()
    {
        $handlerCalled = false;
        $timeout = 1234;
        $handler = function (Call $call, array $options) use (&$handlerCalled, $timeout) {
            $handlerCalled = true;
            $this->assertEquals($timeout, $options['timeoutMillis']);
            return $this->getMockBuilder(Promise::class)
                ->disableOriginalConstructor()
                ->getMock();
        };
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => true,
                'retryableCodes' => [ApiStatus::CANCELLED],
            ]);
        $middleware = new RetryMiddleware($handler, $retrySettings);

        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $options = ['timeoutMillis' => $timeout];
        $middleware($call, $options);
        $this->assertTrue($handlerCalled);
    }

    public function testRetryLogicalTimeout()
    {
        $timeout = 2000;
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with([
                'retriesEnabled' => true,
                'retryableCodes' => [ApiStatus::CANCELLED],
            ])
            ->with(RetrySettings::logicalTimeout($timeout));
        $callCount = 0;
        $observedTimeouts = [];
        $handler = function(Call $call, $options) use (&$callCount, &$observedTimeouts) {
            $callCount += 1;
            $observedTimeouts[] = $options['timeoutMillis'];
            return $promise = new Promise(function () use (&$promise, $callCount) {
                if ($callCount < 3) {
                    throw new ApiException('Cancelled!', Code::CANCELLED, ApiStatus::CANCELLED);
                }
                $promise->resolve('Ok!');
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);
        $response = $middleware(
            $call,
            []
        )->wait();

        $this->assertSame('Ok!', $response);
        $this->assertEquals(3, $callCount);
        $this->assertCount(3, $observedTimeouts);
        $this->assertEquals($observedTimeouts[0], $timeout);
        for ($i = 1; $i < count($observedTimeouts); $i++) {
            $this->assertTrue($observedTimeouts[$i-1] > $observedTimeouts[$i]);
        }
    }

    public function testNoRetryLogicalTimeout()
    {
        $timeout = 2000;
        $call = $this->getMockBuilder(Call::class)
            ->disableOriginalConstructor()
            ->getMock();
        $retrySettings = RetrySettings::constructDefault()
            ->with(RetrySettings::logicalTimeout($timeout));
        $observedTimeout = 0;
        $handler = function(Call $call, $options) use (&$observedTimeout) {
            $observedTimeout = $options['timeoutMillis'];
            return $promise = new Promise(function () use (&$promise) {
                $promise->resolve('Ok!');
            });
        };
        $middleware = new RetryMiddleware($handler, $retrySettings);
        $response = $middleware(
            $call,
            []
        )->wait();

        $this->assertSame('Ok!', $response);
        $this->assertEquals($observedTimeout, $timeout);
    }
}
