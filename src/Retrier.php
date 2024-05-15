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

use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
use Google\ApiCore\Call;
use Google\ApiCore\RetrySettings;
use GuzzleHttp\Promise\PromiseInterface;
use Closure;

/**
 * Class to handle retrying a function with Retry Settings.
 */
class Retrier
{
    /** @var callable */
    private Closure $callable;
    private RetrySettings $retrySettings;

    /*
     * The number of retries that have already been attempted.
     * The original API call will have $retryAttempts set to 0.
     */
    private int $retryAttempts;
    private ?int $timeoutMillis;

    public function __construct(
        callable $callable,
        RetrySettings $retrySettings,
        int $retryAttempts = 0,
        int $timeoutMillis = null,
    ) {
        $this->callable = Closure::FromCallable($callable);
        $this->retrySettings = $retrySettings;
        $this->retryAttempts = $retryAttempts;
        $this->timeoutMillis = $timeoutMillis;
    }

    /**
     * @return PromiseInterface
     */
    public function __invoke(...$parameters)
    {
        $callable = $this->callable;


        // Call the handler immediately if retry settings are disabled.
        if (!$this->retrySettings->retriesEnabled()) {
            return $callable(...$parameters);
        }

        return $callable(...$parameters)->then(null, function ($exception) use ($parameters) {
            $retryFunction = $this->getRetryFunction();

            // If the number of retries has surpassed the max allowed retries
            // then throw the exception as we normally would.
            // If the maxRetries is set to 0, then we don't check this condition.
            if (0 !== $this->retrySettings->getMaxRetries()
                && $this->retryAttempts >= $this->retrySettings->getMaxRetries()
            ) {
                throw $exception;
            }
            // If the retry function returns false then throw the
            // exception as we normally would.
            if (!$retryFunction($exception)) {
                throw $exception;
            }

            // Retry function returned true, so we attempt another retry
            return $this->retry(...$parameters);
        });
    }

    public function getRetrySettings(): RetrySettings
    {
        return $this->retrySettings;
    }

    /**
     * @param Call $call
     * @param array $options
     * @param string $status
     *
     * @return PromiseInterface
     * @throws ApiException
     */
    private function retry(...$parameters)
    {
        $delayMs = $this->retrySettings->getInitialRetryDelayMillis();
        $delayMult = $this->retrySettings->getRetryDelayMultiplier();
        $maxDelayMs = $this->retrySettings->getMaxRetryDelayMillis();
        $timeoutMult = $this->retrySettings->getRpcTimeoutMultiplier();
        $maxTimeoutMs = $this->retrySettings->getMaxRpcTimeoutMillis();
        $totalTimeoutMs = $this->retrySettings->getTotalTimeoutMillis();
        $timeoutMillis = $this->getTimeoutMillis();
        $currentTimeMs = $this->getCurrentTimeMs();
        $deadlineMs = $this->retrySettings->getDeadlineMillis() ?: $currentTimeMs + $totalTimeoutMs;

        if ($currentTimeMs >= $deadlineMs) {
            throw new ApiException(
                'Retry total timeout exceeded.',
                \Google\Rpc\Code::DEADLINE_EXCEEDED,
                ApiStatus::DEADLINE_EXCEEDED
            );
        }

        $newTimeoutMillis = (int) min(
            $timeoutMillis * $timeoutMult,
            $maxTimeoutMs,
            $deadlineMs - $this->getCurrentTimeMs()
        );

        $delayMs = min($delayMs * $delayMult, $maxDelayMs);

        // Update retry settings and other retry parameters
        $this->retrySettings = $this->retrySettings->with([
            'initialRetryDelayMillis' => $delayMs,
            'deadlineMillis' => $deadlineMs,
        ]);
        $this->retryAttempts += 1;
        $this->timeoutMillis = $newTimeoutMillis;

        if ($argumentUpdateFunction = $this->retrySettings->getArgumentUpdateFunction()) {
            $parameters = $argumentUpdateFunction(...$parameters);
        }

        return $this(...$parameters);
    }

    private function getCurrentTimeMs()
    {
        return microtime(true) * 1000.0;
    }

    /**
     * This is the default retry behaviour.
     */
    private function getRetryFunction()
    {
        return $this->retrySettings->getRetryFunction() ??
            function (\Throwable $exception): bool {
                // This is the default retry behaviour, i.e. we don't retry an ApiException
                // and for other exception types, we only retry when the error code is in
                // the list of retryable error codes.
                if (!$exception instanceof ApiException) {
                    return false;
                }

                if (!in_array($exception->getStatus(), $this->retrySettings->getRetryableCodes())) {
                    return false;
                }

                return true;
            };
    }

    /**
     * @experimental
     * @internal
     */
    public function getTimeoutMillis()
    {
        // If timeoutMillis is not set, calculate what it should be for the first invocation
        if (is_null($this->timeoutMillis)) {
            // default to "noRetriesRpcTimeoutMillis" when retries are disabled, otherwise use "initialRpcTimeoutMillis"
            if (!$this->retrySettings->retriesEnabled() && $this->retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
                $this->timeoutMillis = $this->retrySettings->getNoRetriesRpcTimeoutMillis();
            } elseif ($this->retrySettings->getInitialRpcTimeoutMillis() > 0) {
                $this->timeoutMillis = $this->retrySettings->getInitialRpcTimeoutMillis();
            }
        }
        return $this->timeoutMillis;
    }
}
