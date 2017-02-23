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

use Google\GAX\AgentHeaderDescriptor;
use PHPUnit_Framework_TestCase;

class AgentHeaderDescriptorTest extends PHPUnit_Framework_TestCase
{
    public function testWithoutInput()
    {
        $expectedHeader = [AgentHeaderDescriptor::AGENT_HEADER_KEY => [
            'gl-php/' . phpversion() .
            ' gax/' . AgentHeaderDescriptor::GAX_VERSION .
            ' grpc/' . phpversion('grpc')
        ]];

        $agentHeaderDescriptor = new AgentHeaderDescriptor([]);
        $header = $agentHeaderDescriptor->getHeader();

        $this->assertSame($expectedHeader, $header);
    }

    public function testWithInput()
    {
        $expectedHeader = [AgentHeaderDescriptor::AGENT_HEADER_KEY => [
            'gl-php/php-9.9.9 gccl/gccl-9.9.9 gapic/gapic-9.9.9 gax/gax-9.9.9 grpc/grpc-9.9.9' .
            ' additional/additional-9.9.9'
        ]];

        $agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => 'gccl-9.9.9',
            'codeGenName' => 'gapic',
            'codeGenVersion' => 'gapic-9.9.9',
            'gaxVersion' => 'gax-9.9.9',
            'phpVersion' => 'php-9.9.9',
            'grpcVersion' => 'grpc-9.9.9',
            'additionalMetrics' => [
                'additional' => 'additional-9.9.9'
            ]
        ]);
        $header = $agentHeaderDescriptor->getHeader();

        $this->assertSame($expectedHeader, $header);
    }

    public function testWithoutVersionInput()
    {
        $expectedHeader = [AgentHeaderDescriptor::AGENT_HEADER_KEY => [
            'gl-php/' . phpversion() .
            ' gccl/ gapic/ gax/' . AgentHeaderDescriptor::GAX_VERSION .
            ' grpc/' . phpversion('grpc') .
            ' additional/'
        ]];

        $agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'codeGenName' => 'gapic',
            'additionalMetrics' => [
                'additional' => null
            ]
        ]);
        $header = $agentHeaderDescriptor->getHeader();

        $this->assertSame($expectedHeader, $header);
    }

    public function testWithNullVersionInput()
    {
        $expectedHeader = [AgentHeaderDescriptor::AGENT_HEADER_KEY => [
            'gl-php/' . phpversion() .
            ' gccl/ gapic/ gax/' . AgentHeaderDescriptor::GAX_VERSION .
            ' grpc/' . phpversion('grpc') .
            ' additional/'
        ]];

        $agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => 'gccl',
            'libVersion' => null,
            'codeGenName' => 'gapic',
            'codeGenVersion' => null,
            'gaxVersion' => null,
            'phpVersion' => null,
            'grpcVersion' => null,
            'additionalMetrics' => [
                'additional' => null
            ]
        ]);
        $header = $agentHeaderDescriptor->getHeader();

        $this->assertSame($expectedHeader, $header);
    }
}
