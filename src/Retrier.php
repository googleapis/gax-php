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
use Google\ApiCore\RetrySettings;

/**
 * Retrier functionality.
 */
class Retrier
{
    private RetrySettings $retrySettings;

    public function __construct(
        RetrySettings $retrySettings
    ) {
        $this->retrySettings = $retrySettings;
    }

    /**
     * @return RetrySettings
     */
    public function getRetrySettings()
    {
        return $this->retrySettings;
    }

    /**
     * Execute the callable with the retry logic.
     *
     * @param callable $call
     * @param array $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute(callable $call, array $options)
    {
        try {
            return call_user_func_array($call, $options);
        } catch (\Exception $exception) {
            if (!$this->isRetryable($exception)) {
                throw $exception;
            }
            // Check if the deadline has already been exceeded.
            $this->checkDeadlineExceeded($this->getCurrentTimeMillis());
            $this->retrySettings = $this->retrySettings->with([
                'retryAttempts' => $this->retrySettings->getRetryAttempts() + 1
            ]);
            $retryDelayMillis = $this->retrySettings->getRetryDelayMillis($exception);
            // Millis to Micro conversion.
            usleep($retryDelayMillis * 1000);
        }
        $this->execute($call, $options);
    }

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    public function isRetryable(\Exception $exception)
    {
        $retryFunction = $this->getRetryFunction();

        // Don't retry if the number of retries has surpassed the max allowed retries.
        // If the maxRetries is set to 0, then we don't check this condition.
        if (0 !== $this->retrySettings->getMaxRetries()
            && $this->retrySettings->getRetryAttempts() >= $this->retrySettings->getMaxRetries()
        ) {
            return false;
        }
        // Don't retry if the retry function returns false.
        return $retryFunction($this->retrySettings->getRetryAttempts(), $exception);
    }

    /**
     * @param float $currentTimeMillis
     *
     * @return void
     * @throws ApiException
     */
    public function checkDeadlineExceeded(float $currentTimeMillis)
    {
        $deadlineMillis = $this->calculateRetryDeadlineMillis($currentTimeMillis);

        if ($currentTimeMillis >= $deadlineMillis) {
            throw new ApiException(
                'Retry total timeout exceeded.',
                \Google\Rpc\Code::DEADLINE_EXCEEDED,
                ApiStatus::DEADLINE_EXCEEDED
            );
        }
    }

    /**
     * @param float $currentTimeMillis
     *
     * @return float
     */
    public function calculateRetryDeadlineMillis(float $currentTimeMillis)
    {
        if ($this->retrySettings->getDeadlineMillis()) {
            return $this->retrySettings->getDeadlineMillis();
        }
        $totalTimeoutMillis = $this->retrySettings->getTotalTimeoutMillis();
        return $currentTimeMillis + $totalTimeoutMillis;
    }

    /**
     * @return float
     */
    public function getCurrentTimeMillis()
    {
        return microtime(true) * 1000.0;
    }

    /**
     * This is the default retry behaviour.
     *
     * @return callable
     */
    private function getRetryFunction()
    {
        return $this->retrySettings->getRetryFunction() ??
            function (int $retryAttempts, \Throwable $e): bool {
                // This is the default retry behaviour, i.e. we don't retry an ApiException
                // and for other exception types, we only retry when the error code is in
                // the list of retryable error codes.
                if (!$e instanceof ApiException) {
                    return false;
                }

                if (!in_array($e->getStatus(), $this->retrySettings->getRetryableCodes())) {
                    return false;
                }

                return true;
            };
    }
}
