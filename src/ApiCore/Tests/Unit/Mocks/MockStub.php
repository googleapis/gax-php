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

namespace Google\ApiCore\Tests\Unit\Mocks;

use Google\ApiCore\Testing\MockStubTrait;
use Google\ApiCore\Testing\MockUnaryCall;
use Google\ApiCore\Testing\ReceivedRequest;
use Google\ApiCore\ValidationException;
use InvalidArgumentException;
use UnderflowException;

class MockStub
{
    use MockStubTrait;

    private $deserialize;

    public function __construct($deserialize = null)
    {
        $this->deserialize = $deserialize;
    }

    /**
     * @param mixed $responseObject
     * @param $status
     * @return MockStub
     */
    public static function create($responseObject, $status = null)
    {
        $stub = new MockStub();
        $stub->addResponse($responseObject, $status);
        return $stub;
    }

    /**
     * Creates a sequence such that the responses are returned in order.
     * @param mixed[] $sequence
     * @param callable $deserialize
     * @return MockStub
     */
    public static function createWithResponseSequence($sequence, $deserialize = null)
    {
        $stub = new MockStub($deserialize);
        foreach ($sequence as $elem) {
            list($resp, $status) = $elem;
            $stub->addResponse($resp, $status);
        }
        return $stub;
    }

    public function __call($name, $arguments)
    {
        list($argument, $metadata, $options) = $arguments;
        $newArgs = [$name, $argument, $this->deserialize, $metadata, $options];
        return call_user_func_array(array($this, '_simpleRequest'), $newArgs);
    }

    public function methodThatSleeps($argument, $metadata, $options)
    {
        $this->receivedFuncCalls[] = new ReceivedRequest(
            'methodThatSleeps',
            $argument,
            $this->deserialize,
            $metadata,
            $options
        );
        $timeoutMicros = isset($options['timeout']) ? $options['timeout'] : null;
        $call = new MockDeadlineExceededUnaryCall($timeoutMicros);
        $this->callObjects[] = $call;
        return $call;
    }
}
