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

namespace Google\ApiCore;

use Google\ApiCore\Middleware\RetryMiddleware;

/**
 * This is a helper class to achieve block level retries.
 *
 * @internal
 */
class Retry
{
    /** @var RetrySettings $retrySettings */
    private RetrySettings $retrySettings;

    /**
     * @param RetrySettings $retrySettings
     */
    public function __construct(RetrySettings $retrySettings)
    {
        $this->retrySettings = $retrySettings;
    }

    /**
     * Executes the retry process.
     *
     * @param callable $call The callable to execute.
     * @param array $arguments [optional]
     * @return mixed
     * @throws \Exception The last exception caught while retrying.
     */
    public function execute(callable $call, $arguments = []): mixed
    {
        $callStack = fn($method, $args) => call_user_func_array($method, $args);
        $callStack = new RetryMiddleware($callStack, $this->retrySettings);
        return $callStack($call, $arguments);
    }

    /**
     * @return RetrySettings
     */
    public function getRetrySettings(): RetrySettings
    {
        return $this->retrySettings;
    }

    /**
     * @param RetrySettings $retrySettings
     */
    public function setRetrySettings(RetrySettings $retrySettings): void
    {
        $this->retrySettings = $retrySettings;
    }
}
