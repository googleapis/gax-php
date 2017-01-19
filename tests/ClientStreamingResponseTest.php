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

use Google\GAX\ApiException;
use Google\GAX\ClientStreamingResponse;
use Google\GAX\OperationResponse;
use Google\GAX\ServerStreamingResponse;
use Google\GAX\Testing\MockClientStreamingCall;
use Google\GAX\Testing\MockServerStreamingCall;
use Google\GAX\UnitTests\Mocks\MockPageStreamingResponse;
use Google\GAX\UnitTests\Mocks\MockStatus;
use google\rpc\Status;
use Grpc;
use PHPUnit_Framework_TestCase;

class ClientStreamingResponseTest extends PHPUnit_Framework_TestCase
{
    public function testNoWritesSuccess()
    {
        $response = 'response';
        $call = new MockClientStreamingCall($response);
        $resp = new ClientStreamingResponse($call);

        $this->assertSame($call, $resp->getClientStreamingCall());
        $this->assertSame($response, $resp->readResponse());
        $this->assertSame([], $call->getReceivedCalls());
    }

    /**
     * @expectedException \Google\GAX\ApiException
     * @expectedExceptionMessage no writes failure
     */
    public function testNoWritesFailure()
    {
        $response = 'response';
        $call = new MockClientStreamingCall(
            $response,
            null,
            new MockStatus(Grpc\STATUS_INTERNAL, 'no writes failure')
        );
        $resp = new ClientStreamingResponse($call);

        $this->assertSame($call, $resp->getClientStreamingCall());
        $this->assertSame([], $call->getReceivedCalls());
        $resp->readResponse();
    }

    public function testManualWritesSuccess()
    {
        $requests = [
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request1'),
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request2')
        ];
        $response = ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'response');
        $call = new MockClientStreamingCall($response->serialize(), '\google\rpc\Status::deserialize');
        $resp = new ClientStreamingResponse($call);

        foreach ($requests as $request) {
            $resp->write($request);
        }

        $this->assertSame($call, $resp->getClientStreamingCall());
        $this->assertEquals($response, $resp->readResponse());
        $this->assertEquals($requests, $call->getReceivedCalls());
    }

    /**
     * @expectedException \Google\GAX\ApiException
     * @expectedExceptionMessage manual writes failure
     */
    public function testManualWritesFailure()
    {
        $requests = [
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request1'),
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request2')
        ];
        $response = ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'response');
        $call = new MockClientStreamingCall(
            $response->serialize(),
            '\google\rpc\Status::deserialize',
            new MockStatus(Grpc\STATUS_INTERNAL, 'manual writes failure')
        );
        $resp = new ClientStreamingResponse($call);

        foreach ($requests as $request) {
            $resp->write($request);
        }

        $this->assertSame($call, $resp->getClientStreamingCall());
        $this->assertEquals($requests, $call->getReceivedCalls());
        $resp->readResponse();
    }

    public function testWriteAllSuccess()
    {
        $requests = [
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request1'),
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request2')
        ];
        $response = ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'response');
        $call = new MockClientStreamingCall($response->serialize(), '\google\rpc\Status::deserialize');
        $resp = new ClientStreamingResponse($call);

        $actualResponse = $resp->writeAllAndReadResponse($requests);

        $this->assertSame($call, $resp->getClientStreamingCall());
        $this->assertEquals($response, $actualResponse);
        $this->assertEquals($requests, $call->getReceivedCalls());
    }

    /**
     * @expectedException \Google\GAX\ApiException
     * @expectedExceptionMessage write all failure
     */
    public function testWriteAllFailure()
    {
        $requests = [
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request1'),
            ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'request2')
        ];
        $response = ClientStreamingResponseTest::createStatus(Grpc\STATUS_OK, 'response');
        $call = new MockClientStreamingCall($response->serialize(), '\google\rpc\Status::deserialize', new MockStatus(Grpc\STATUS_INTERNAL, 'write all failure'));
        $resp = new ClientStreamingResponse($call);

        try {
            $resp->writeAllAndReadResponse($requests);
        } finally {
            $this->assertSame($call, $resp->getClientStreamingCall());
            $this->assertEquals($requests, $call->getReceivedCalls());
        }
    }

    private static function createStatus($code, $message)
    {
        $status = new Status();
        $status->setCode($code)->setMessage($message);
        return $status;
    }
}
