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

namespace Google\ApiCore\Tests\Unit\Transport\Grpc;

use Google\ApiCore\Tests\Unit\TestTrait;
use Google\ApiCore\Transport\Grpc\LoggingUnaryCall;
use Google\Rpc\Code;
use Grpc\UnaryCall;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use stdClass;

class LoggingUnaryCallTest extends TestCase
{
    use TestTrait;

    public function testLoggingUnaryCall()
    {
        $responseString = "response-string";
        $status = new stdClass;
        $status->code = Code::OK;
        $unaryCall = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unaryCall->expects($this->once())
            ->method('wait')
            ->willReturn([$responseString, $status])
        ;

        $expectedLogs = [
            [LogLevel::INFO, "formatResponse[$responseString]", []]
        ];

        $testLogger = new TestLogger();
        $loggingUnaryCall = new LoggingUnaryCall($unaryCall, new TestUnaryCallLogger($testLogger));

        $loggingUnaryCall->wait();
        $this->assertEquals($expectedLogs, $testLogger->getLogs());
    }
}
