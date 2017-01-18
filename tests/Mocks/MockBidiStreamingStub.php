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

namespace Google\GAX\UnitTests\Mocks;

use Google\GAX\Testing\MockStubTrait;
use InvalidArgumentException;

class MockBidiStreamingStub
{
    use MockStubTrait;

    private $deserialize;

    public function __construct($deserialize = null)
    {
        $this->deserialize = $deserialize;
    }

    /**
     * Creates a sequence such that the responses are returned in order.
     * @param mixed[] $sequence
     * @param $finalStatus
     * @param callable $deserialize
     * @return MockBidiStreamingStub
     */
    public static function createWithResponseSequence($sequence, $finalStatus = null, $deserialize = null)
    {
        if (count($sequence) == 0) {
            throw new InvalidArgumentException("createResponseSequence: need at least 1 response");
        }
        $stub = new MockBidiStreamingStub($deserialize);
        foreach ($sequence as $resp) {
            $stub->addResponse($resp);
        }
        $stub->setStreamingStatus($finalStatus);
        return $stub;
    }

    public function __call($name, $arguments)
    {
        list($metadata, $options) = $arguments;
        $newArgs = [$name, $this->deserialize, $metadata, $options];
        return call_user_func_array(array($this, '_bidiRequest'), $newArgs);
    }
}
