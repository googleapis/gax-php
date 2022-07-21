<?php
/*
 * Copyright 2016 Google LLC
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

use Google\ApiCore\ApiException;
use Google\ApiCore\BidiStream;
use Google\ApiCore\Testing\MockBidiStreamingCall;
use Google\ApiCore\Testing\MockStatus;
use Google\ApiCore\ValidationException;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Code;
use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectException;

class BidiStreamTest extends TestCase
{
    use ExpectException;
    use TestTrait;

    public function testEmptySuccess()
    {
        $call = new MockBidiStreamingCall([]);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertSame([], iterator_to_array($stream->closeWriteAndReadAll()));
    }

    public function testEmptyFailureRead()
    {
        $call = new MockBidiStreamingCall([], null, new MockStatus(Code::INTERNAL, 'empty failure read'));
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $stream->closeWrite();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('empty failure read');

        $stream->read();
    }

    public function testEmptyFailureReadAll()
    {
        $call = new MockBidiStreamingCall([], null, new MockStatus(Code::INTERNAL, 'empty failure readall'));
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('empty failure readall');

        iterator_to_array($stream->closeWriteAndReadAll());
    }

    public function testReadAfterComplete()
    {
        $call = new MockBidiStreamingCall([]);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $stream->closeWrite();
        $this->assertNull($stream->read());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot call read() after streaming call is complete.');

        $stream->read();
    }

    public function testWriteAfterComplete()
    {
        $call = new MockBidiStreamingCall([]);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $stream->closeWrite();
        $this->assertNull($stream->read());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot call write() after streaming call is complete.');

        $stream->write('request');
    }

    public function testWriteAfterCloseWrite()
    {
        $call = new MockBidiStreamingCall([]);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $stream->closeWrite();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot call write() after calling closeWrite().');

        $stream->write('request');
    }

    public function testReadStringsSuccess()
    {
        $responses = ['abc', 'def'];
        $call = new MockBidiStreamingCall($responses);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertSame($responses, iterator_to_array($stream->closeWriteAndReadAll()));
    }

    public function testReadObjectsSuccess()
    {
        $responses = [
            $this->createStatus(Code::OK, 'response1'),
            $this->createStatus(Code::OK, 'response2')
        ];
        $serializedResponses = [];
        foreach ($responses as $response) {
            $serializedResponses[] = $response->serializeToString();
        }
        $call = new MockBidiStreamingCall($serializedResponses, ['\Google\Rpc\Status', 'mergeFromString']);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertEquals($responses, iterator_to_array($stream->closeWriteAndReadAll()));
    }

    public function testReadCloseReadSuccess()
    {
        $responses = [
            $this->createStatus(Code::OK, 'response1'),
            $this->createStatus(Code::OK, 'response2')
        ];
        $serializedResponses = [];
        foreach ($responses as $response) {
            $serializedResponses[] = $response->serializeToString();
        }
        $call = new MockBidiStreamingCall($serializedResponses, ['\Google\Rpc\Status', 'mergeFromString']);
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $response = $stream->read();
        $stream->closeWrite();
        $index = 0;
        while (!is_null($response)) {
            $this->assertEquals($response, $responses[$index]);
            $response = $stream->read();
            $index++;
        }
    }

    public function testReadFailure()
    {
        $responses = ['abc', 'def'];
        $call = new MockBidiStreamingCall(
            $responses,
            null,
            new MockStatus(Code::INTERNAL, 'read failure')
        );
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('read failure');

        $index = 0;
        try {
            foreach ($stream->closeWriteAndReadAll() as $response) {
                $this->assertSame($response, $responses[$index]);
                $index++;
            }
        } finally {
            $this->assertSame(2, $index);
        }
    }

    public function testWriteStringsSuccess()
    {
        $requests = ['request1', 'request2'];
        $responses = [];
        $call = new MockBidiStreamingCall($responses);
        $stream = new BidiStream($call);

        $stream->writeAll($requests);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertSame([], iterator_to_array($stream->closeWriteAndReadAll()));
        $this->assertEquals($requests, $call->popReceivedCalls());
    }

    public function testWriteObjectsSuccess()
    {
        $requests = [
            $this->createStatus(Code::OK, 'request1'),
            $this->createStatus(Code::OK, 'request2')
        ];
        $responses = [];
        $call = new MockBidiStreamingCall($responses, ['\Google\Rpc\Status', 'mergeFromString']);
        $stream = new BidiStream($call);

        $stream->writeAll($requests);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertSame([], iterator_to_array($stream->closeWriteAndReadAll()));
        $this->assertEquals($requests, $call->popReceivedCalls());
    }

    public function testAlternateReadWriteObjectsSuccess()
    {
        $requests = [
            $this->createStatus(Code::OK, 'request1'),
            $this->createStatus(Code::OK, 'request2'),
            $this->createStatus(Code::OK, 'request3')
        ];
        $responses = [
            $this->createStatus(Code::OK, 'response1'),
            $this->createStatus(Code::OK, 'response2'),
            $this->createStatus(Code::OK, 'response3'),
            $this->createStatus(Code::OK, 'response4')
        ];
        $serializedResponses = [];
        foreach ($responses as $response) {
            $serializedResponses[] = $response->serializeToString();
        }
        $call = new MockBidiStreamingCall($serializedResponses, ['\Google\Rpc\Status', 'mergeFromString']);
        $stream = new BidiStream($call);

        $index = 0;
        foreach ($requests as $request) {
            $stream->write($request);
            $response = $stream->read();
            $this->assertEquals($response, $responses[$index]);
            $index++;
        }
        $stream->closeWrite();
        $response = $stream->read();
        while (!is_null($response)) {
            $this->assertEquals($response, $responses[$index]);
            $index++;
            $response = $stream->read();
        }

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertEquals($requests, $call->popReceivedCalls());
    }

    public function testWriteFailureWithoutClose()
    {
        $request = 'request';
        $responses = [null];
        $call = new MockBidiStreamingCall(
            $responses,
            null,
            new MockStatus(Code::INTERNAL, 'write failure without close')
        );
        $stream = new BidiStream($call);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $stream->write($request);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('write failure without close');

        try {
            $stream->read();
        } finally {
            $this->assertEquals([$request], $call->popReceivedCalls());
        }
    }

    public function testResourcesSuccess()
    {
        $resources = ['resource1', 'resource2', 'resource3'];
        $repeatedField1 = new RepeatedField(GPBType::STRING);
        $repeatedField1[] = 'resource1';
        $repeatedField2 = new RepeatedField(GPBType::STRING);
        $repeatedField2[] = 'resource2';
        $repeatedField2[] = 'resource3';
        $responses = [
            $this->createMockResponse('nextPageToken1', $repeatedField1),
            $this->createMockResponse('nextPageToken1', $repeatedField2)
        ];
        $call = new MockBidiStreamingCall($responses);
        $stream = new BidiStream($call, [
            'resourcesGetMethod' => 'getResourcesList'
        ]);

        $this->assertSame($call, $stream->getBidiStreamingCall());
        $this->assertEquals($resources, iterator_to_array($stream->closeWriteAndReadAll()));
    }
}
