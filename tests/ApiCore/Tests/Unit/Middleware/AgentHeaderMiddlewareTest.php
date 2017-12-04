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

use Google\ApiCore\AgentHeaderDescriptor;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\Tests\Unit\TestTrait;
use Google\ApiCore\Tests\Mocks\MockTransport;
use Google\Rpc\Code;
use PHPUnit\Framework\TestCase;

class AgentHeaderMiddlewareTest extends TestCase
{
    use TestTrait;

    public function testCustomHeader()
    {
        $transport = MockTransport::create($this->createMockResponse());
        $headerDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => '0.0.0',
            'gapicVersion' => '0.9.0',
            'apiCoreVersion' => '1.0.0',
            'phpVersion' => '5.5.0',
            'grpcVersion' => '1.0.1'
        ]);
        $callSettings = new CallSettings();
        $transport->setAgentHeaderDescriptor($headerDescriptor);
        $response = $transport->startCall(
            $mockCall = new Call('takeAction', null),
            $callSettings
        )->wait();
        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(1, count($actualCalls));
        $expectedMetadata = [
            'x-goog-api-client' => ['gl-php/5.5.0 gccl/0.0.0 gapic/0.9.0 gax/1.0.0 grpc/1.0.1']
        ];
        $this->assertEquals($expectedMetadata, $actualCalls[0]->getMetadata());
    }

    public function testUserHeaders()
    {
        $transport = MockTransport::create($this->createMockResponse());
        $headerDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => '0.0.0',
            'gapicVersion' => '0.9.0',
            'apiCoreVersion' => '1.0.0',
            'phpVersion' => '5.5.0',
            'grpcVersion' => '1.0.1'
        ]);
        $userHeaders = [
            'google-cloud-resource-prefix' => ['my-database'],
        ];
        $callSettings = new CallSettings([
            'userHeaders' => $userHeaders,
        ]);
        $transport->setAgentHeaderDescriptor($headerDescriptor);
        $response = $transport->startCall(
            $mockCall = new Call('takeAction', null),
            $callSettings
        )->wait();
        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(1, count($actualCalls));
        $expectedMetadata = [
            'x-goog-api-client' => ['gl-php/5.5.0 gccl/0.0.0 gapic/0.9.0 gax/1.0.0 grpc/1.0.1'],
            'google-cloud-resource-prefix' => ['my-database'],
        ];
        $this->assertEquals($expectedMetadata, $actualCalls[0]->getMetadata());
    }

    public function testUserHeadersOverwriteBehavior()
    {
        $transport = MockTransport::create($this->createMockResponse());
        $headerDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => '0.0.0',
            'gapicVersion' => '0.9.0',
            'apiCoreVersion' => '1.0.0',
            'phpVersion' => '5.5.0',
            'grpcVersion' => '1.0.1'
        ]);
        $userHeaders = [
            'x-goog-api-client' => ['this-should-not-be-used'],
            'new-header' => ['this-should-be-used']
        ];
        $callSettings = new CallSettings([
            'userHeaders' => $userHeaders,
        ]);
        $transport->setAgentHeaderDescriptor($headerDescriptor);
        $response = $transport->startCall(
            $mockCall = new Call('takeAction', null),
            $callSettings
        )->wait();
        $actualCalls = $transport->popReceivedCalls();
        $this->assertEquals(1, count($actualCalls));
        $expectedMetadata = [
            'x-goog-api-client' => ['gl-php/5.5.0 gccl/0.0.0 gapic/0.9.0 gax/1.0.0 grpc/1.0.1'],
            'new-header' => ['this-should-be-used'],
        ];
        $this->assertEquals($expectedMetadata, $actualCalls[0]->getMetadata());
    }
}
