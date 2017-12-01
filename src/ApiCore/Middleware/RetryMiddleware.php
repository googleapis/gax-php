<?php
/*
 * Copyright 2017, Google Inc.
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
namespace Google\ApiCore\Middleware;

use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;

/**
 * Middleware that adds retry functionality.
 */
class RetryMiddleware
{
    /** @var callable */
    private $nextHandler;

    /** @var float|null */
    private $deadlineMs;

    public function __construct(
        callable $nextHandler,
        $deadlineMs = null
    ) {
        $this->nextHandler = $nextHandler;
        $this->deadlineMs = $deadlineMs;
    }

    public function __invoke(Call $call, CallSettings $settings)
    {
        $nextHandler = $this->nextHandler;

        return $nextHandler($call, $settings)
            ->then(null, function (\Exception $e) use ($call, $settings) {
                if (!$e instanceof ApiException) {
                    throw $e;
                }

                if (!in_array($e->getStatus(), $settings->getRetrySettings()->getRetryableCodes())) {
                    throw $e;
                }

                return $this->retry($call, $settings, $e->getStatus());
            });
    }

    /**
     * @param Call $call
     * @param CallSettings $settings The call settings to use for this call.
     *
     * @return PromiseInterface
     */
    private function retry(Call $call, CallSettings $settings, $status)
    {
        $retrySettings = $settings->getRetrySettings();
        $delayMult = $retrySettings->getRetryDelayMultiplier();
        $maxDelayMs = $retrySettings->getMaxRetryDelayMillis();
        $timeoutMult = $retrySettings->getRpcTimeoutMultiplier();
        $maxTimeoutMs = $retrySettings->getMaxRpcTimeoutMillis();
        $totalTimeoutMs = $retrySettings->getTotalTimeoutMillis();

        $delayMs = $retrySettings->getInitialRetryDelayMillis();
        $timeoutMs = $retrySettings->getInitialRpcTimeoutMillis();
        $currentTimeMs = $this->getCurrentTimeMs();
        $deadlineMs = $this->deadlineMs ?: $currentTimeMs + $totalTimeoutMs;

        if ($currentTimeMs >= $deadlineMs) {
            throw new ApiException(
                'Retry total timeout exceeded.',
                \Google\Rpc\Code::DEADLINE_EXCEEDED,
                ApiStatus::DEADLINE_EXCEEDED
            );
        }

        // Don't sleep if the failure was a timeout
        if ($status != ApiStatus::DEADLINE_EXCEEDED) {
            usleep($delayMs * 1000);
        }
        $delayMs = min($delayMs * $delayMult, $maxDelayMs);
        $timeoutMs = min(
            $timeoutMs * $timeoutMult,
            $maxTimeoutMs,
            $deadlineMs - $this->getCurrentTimeMs()
        );

        $nextHandler = new RetryMiddleware(
            $this->nextHandler,
            $deadlineMs
        );

        return $nextHandler(
            $call,
            $settings->with([
                'retrySettings' => $retrySettings->with([
                    'initialRpcTimeoutMillis' => $timeoutMs,
                    'initialRetryDelayMillis' => $delayMs
                ])
            ])
        );
    }

    protected function getCurrentTimeMs()
    {
        return microtime(true) * 1000.0;
    }
}
