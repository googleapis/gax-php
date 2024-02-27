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

namespace Google\ApiCore\Tests\Unit\Middleware;

use Google\ApiCore\Call;
use Google\ApiCore\Middleware\RequestAutoPopulationMiddleware;
use Google\ApiCore\Testing\MockRequest;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RequestAutoPopulationMiddlewareTest extends TestCase
{
    public function testRequestPopulated()
    {
        $request = new MockRequest();
        $next = function ($call, $options) {
            $this->assertTrue(Uuid::isValid($call->getMessage()->getPageToken()));
            return true;
        };
        $call = new Call('GetExample', 'Example', $request);
        $middleware = new RequestAutoPopulationMiddleware(
            $next,
            ['pageToken' => 'UUID4']
        );
        $this->assertTrue($middleware->__invoke($call, []));
    }

    public function testRequestNotPopulated()
    {
        $request = new MockRequest();
        $next = function ($call, $options) {
            $this->assertTrue(empty($call->getMessage()->getPageToken()));
            return true;
        };
        $call = new Call('GetExample', 'Example', $request);
        $middleware = new RequestAutoPopulationMiddleware(
            $next,
            ['pageToken' => 'UNKNOWN']
        );
        $this->assertTrue($middleware->__invoke($call, []));
    }
}
