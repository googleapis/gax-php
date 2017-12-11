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

namespace Google\ApiCore\Tests\Unit\Middleware;

use Google\ApiCore\ApiException;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\ApiStatus;
use Google\ApiCore\Tests\Mocks\MockStatus;
use Google\ApiCore\Tests\Unit\TestTrait;
use Google\ApiCore\Tests\Mocks\MockTransport;
use Google\Protobuf\Internal\Message;
use Google\Rpc\Code;
use PHPUnit\Framework\TestCase;
use stdClass;

class RetryMiddlewareTest extends TestCase
{
    use TestTrait;

    public function testRetryNoRetryableCode()
    {
        $response = "response";
        $request = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $status = new stdClass;
        $status->code = Code::DEADLINE_EXCEEDED;
        $status->details = 'Deadline Exceeded';

        $transport = MockTransport::createWithResponseSequence([[$response, $status]]);
        $retrySettings = new RetrySettings([
            'initialRetryDelayMillis' => 100,
            'retryDelayMultiplier' => 1.3,
            'maxRetryDelayMillis' => 400,
            'initialRpcTimeoutMillis' => 150,
            'rpcTimeoutMultiplier' => 2,
            'maxRpcTimeoutMillis' => 600,
            'totalTimeoutMillis' => 2000,
            'retryableCodes' => [],
        ]);
        $isExceptionRaised = false;
        $call = new Call('takeAction', null, $request);
        $callSettings = new CallSettings(['retrySettings' => $retrySettings]);
        try {
            $response = $transport->startCall(
                $call,
                $callSettings
            )->wait();
        } catch (ApiException $e) {
            $isExceptionRaised = true;
        }

        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(1, count($actualCalls));
        $this->assertEquals($request, $actualCalls[0]->getRequestObject());

        $this->assertTrue($isExceptionRaised);
    }

    public function testRetryBackoff()
    {
        $request = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $statusDeadlineExceeded = new stdClass;
        $statusDeadlineExceeded->code = Code::DEADLINE_EXCEEDED;
        $statusDeadlineExceeded->details = 'Deadline Exceeded';
        $statusOk = new stdClass;
        $statusOk->code = Code::OK;
        $responseSequence = [
            ['responseA', $statusDeadlineExceeded],
            ['responseB', $statusDeadlineExceeded],
            ['responseC', $statusOk]
        ];
        $transport = MockTransport::createWithResponseSequence($responseSequence);
        $retrySettings = new RetrySettings([
            'initialRetryDelayMillis' => 100,
            'retryDelayMultiplier' => 1.3,
            'maxRetryDelayMillis' => 400,
            'initialRpcTimeoutMillis' => 150,
            'rpcTimeoutMultiplier' => 2,
            'maxRpcTimeoutMillis' => 500,
            'totalTimeoutMillis' => 2000,
            'retryableCodes' => [ApiStatus::DEADLINE_EXCEEDED],
        ]);
        $call = new Call('takeAction', null, $request);
        $callSettings = new CallSettings(['retrySettings' => $retrySettings]);
        $actualResponse = $transport->startCall(
            $call,
            $callSettings
        )->wait();

        $this->assertEquals('responseC', $actualResponse);

        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(3, count($actualCalls));

        $this->assertEquals($request, $actualCalls[0]->getRequestObject());
        $this->assertEquals(['timeout' => 150], $actualCalls[0]->getOptions());

        $this->assertEquals($request, $actualCalls[1]->getRequestObject());
        $this->assertEquals(['timeout' => 300], $actualCalls[1]->getOptions());

        $this->assertEquals($request, $actualCalls[2]->getRequestObject());
        $this->assertEquals(['timeout' => 500], $actualCalls[2]->getOptions());
    }

    public function testRetryTimeoutExceedsMaxTimeout()
    {
        $request = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response = 'response';
        $status = new stdClass;
        $status->code = Code::DEADLINE_EXCEEDED;
        $status->details = 'Deadline Exceeded';
        $transport = MockTransport::createWithResponseSequence([
            [$response, $status],
            [$response, $status],
            [$response, $status]
        ]);
        $retrySettings = new RetrySettings([
            'initialRetryDelayMillis' => 1000,
            'retryDelayMultiplier' => 1.3,
            'maxRetryDelayMillis' => 4000,
            'initialRpcTimeoutMillis' => 150,
            'rpcTimeoutMultiplier' => 2,
            'maxRpcTimeoutMillis' => 600,
            'totalTimeoutMillis' => 0,
            'retryableCodes' => [ApiStatus::DEADLINE_EXCEEDED],
        ]);
        $raisedException = null;
        $call = new Call('takeAction', null, $request);
        $callSettings = new CallSettings(['retrySettings' => $retrySettings]);
        try {
            $transport->startCall(
                $call,
                $callSettings
            )->wait();
        } catch (ApiException $e) {
            $raisedException = $e;
        }

        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(1, count($actualCalls));
        $this->assertEquals($request, $actualCalls[0]->getRequestObject());

        $this->assertNotNull($raisedException);
        $this->assertEquals(Code::DEADLINE_EXCEEDED, $raisedException->getCode());
    }

    public function testRetryTimeoutExceedsRealTime()
    {
        $request = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response = "response";
        $status = new stdClass;
        $status->code = Code::DEADLINE_EXCEEDED;
        $status->details = 'Deadline Exceeded';
        $transport = MockTransport::createWithResponseSequence([
            [$response, $status]
        ]);
        $retrySettings = new RetrySettings([
            'initialRetryDelayMillis' => 10,
            'retryDelayMultiplier' => 1,
            'maxRetryDelayMillis' => 10,
            'initialRpcTimeoutMillis' => 500,
            'rpcTimeoutMultiplier' => 1,
            'maxRpcTimeoutMillis' => 500,
            'totalTimeoutMillis' => 1000,
            'retryableCodes' => [ApiStatus::DEADLINE_EXCEEDED],
        ]);
        $raisedException = null;
        $call = new Call('methodThatSleeps', null, $request);
        $callSettings = new CallSettings(['retrySettings' => $retrySettings]);
        try {
            $response = $transport->startCall(
                $call,
                $callSettings
            )->wait();
        } catch (ApiException $e) {
            $raisedException = $e;
        }

        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(3, count($actualCalls));
        $this->assertEquals($request, $actualCalls[0]->getRequestObject());

        $this->assertNotNull($raisedException);
        $this->assertEquals(Code::DEADLINE_EXCEEDED, $raisedException->getCode());
    }
}
