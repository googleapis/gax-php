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
use Google\GAX\BidiStreamingResponse;
use Google\GAX\Testing\MockBidiStreamingCall;
use Google\GAX\UnitTests\Mocks\MockStatus;
use google\rpc\Status;
use Grpc;
use PHPUnit_Framework_TestCase;

class BidiStreamingResponseTest extends PHPUnit_Framework_TestCase
{
    public function testEmptySuccess()
    {
        $call = new MockBidiStreamingCall([]);
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        $this->assertSame([], iterator_to_array($resp->closeWriteAndReadAll()));
    }

    /**
     * @expectedException \Google\GAX\ApiException
     * @expectedExceptionMessage empty failure read
     */
    public function testEmptyFailureRead()
    {
        $call = new MockBidiStreamingCall([], null, new MockStatus(Grpc\STATUS_INTERNAL, 'empty failure read'));
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        $resp->closeWrite();
        $resp->read();
    }

    /**
     * @expectedException \Google\GAX\ApiException
     * @expectedExceptionMessage empty failure readall
     */
    public function testEmptyFailureReadAll()
    {
        $call = new MockBidiStreamingCall([], null, new MockStatus(Grpc\STATUS_INTERNAL, 'empty failure readall'));
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        iterator_to_array($resp->closeWriteAndReadAll());
    }

    /**
     * @expectedException \Google\GAX\ValidationException
     * @expectedExceptionMessage Cannot call read() after streaming call is complete.
     */
    public function testReadAfterComplete()
    {
        $call = new MockBidiStreamingCall([]);
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        $resp->closeWrite();
        $this->assertNull($resp->read());
        $resp->read();
    }

    /**
     * @expectedException \Google\GAX\ValidationException
     * @expectedExceptionMessage Cannot call write() after streaming call is complete.
     */
    public function testWriteAfterComplete()
    {
        $call = new MockBidiStreamingCall([]);
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        $resp->closeWrite();
        $this->assertNull($resp->read());
        $resp->write('request');
    }

    /**
     * @expectedException \Google\GAX\ValidationException
     * @expectedExceptionMessage Cannot call write() after calling closeWrite().
     */
    public function testWriteAfterCloseWrite()
    {
        $call = new MockBidiStreamingCall([]);
        $resp = new BidiStreamingResponse($call);

        $this->assertSame($call, $resp->getBidiStreamingCall());
        $resp->closeWrite();
        $resp->write('request');
    }

    private static function createStatus($code, $message)
    {
        $status = new Status();
        $status->setCode($code)->setMessage($message);
        return $status;
    }
}
