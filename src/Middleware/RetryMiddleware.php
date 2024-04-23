<?php
/*
 * Copyright 2018 Google LLC
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

use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
use Google\ApiCore\Call;
use Google\ApiCore\Retrier;
use Google\ApiCore\RetrySettings;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Middleware that adds retry functionality.
 */
class RetryMiddleware implements MiddlewareInterface
{
    /** @var callable */
    private $nextHandler;
    private Retrier $retrier;

    /**
     * @param callable $nextHandler
     * @param RetrySettings $retrySettings
     * @param int $deadlineMillis TODO: Deprecate if possible.
     * @param int $retryAttempts TODO: Deprecate if possible.
     */
    public function __construct(
        callable $nextHandler,
        RetrySettings $retrySettings,
        $deadlineMillis = null,
        $retryAttempts = 0
    ) {
        $this->nextHandler = $nextHandler;
        $retrySettings = $retrySettings->with([
            'deadlineMillis' => $deadlineMillis,
            'retryAttempts' => $retryAttempts
        ]);
        $this->retrier = new Retrier($retrySettings);
    }

    /**
     * @param Call $call
     * @param array $options
     *
     * @return PromiseInterface
     */
    public function __invoke(Call $call, array $options)
    {
        $nextHandler = $this->nextHandler;
        $retrySettings = $this->retrier->getRetrySettings();

        if (!isset($options['timeoutMillis'])) {
            // default to "noRetriesRpcTimeoutMillis" when retries are disabled, otherwise use "initialRpcTimeoutMillis"
            if (!$retrySettings->retriesEnabled() && $retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
                $options['timeoutMillis'] = $retrySettings->getNoRetriesRpcTimeoutMillis();
            } elseif ($retrySettings->getInitialRpcTimeoutMillis() > 0) {
                $options['timeoutMillis'] = $retrySettings->getInitialRpcTimeoutMillis();
            }
        }

        // Call the handler immediately if retry settings are disabled.
        if (!$retrySettings->retriesEnabled()) {
            return $nextHandler($call, $options);
        }

        return $nextHandler($call, $options)->then(null, function ($exception) use ($call, $options) {
            if (!$this->retrier->isRetryable($exception)) {
                throw $exception;
            }

            // Retry function returned true, so we attempt another retry
            return $this->retry($call, $options, $exception);
        });
    }

    /**
     * @param Call $call
     * @param array $options
     * @param ApiException $exception
     *
     * @return PromiseInterface
     * @throws ApiException
     */
    private function retry(Call $call, array $options, ApiException $exception)
    {
        $currentTime = $this->retrier->getCurrentTimeMillis();
        $this->retrier->checkDeadlineExceeded($currentTime);
        $deadlineMillis = $this->retrier->calculateRetryDeadlineMillis($currentTime);
        $retrySettings = $this->retrier->getRetrySettings()->with([
            'retryAttempts' => $this->retrier->getRetrySettings()->getRetryAttempts() + 1,
            'deadlineMillis' => $deadlineMillis
        ]);
        $timeout = $this->calculateTimeoutMillis(
            $retrySettings,
            $options['timeoutMillis']
        );

        $nextHandler = new RetryMiddleware(
            $this->nextHandler,
            $retrySettings,
            $retrySettings->getDeadlineMillis(),
            $retrySettings->getRetryAttempts()
        );

        // Set the timeout for the call
        $options['timeoutMillis'] = $timeout;

        return $nextHandler(
            $call,
            $options
        );
    }

    /**
     * Calculates the timeout for the call.
     *
     * @param RetrySettings $retrySettings
     * @param int $timeoutMillis
     *
     * @return int
     */
    private function calculateTimeoutMillis(
        RetrySettings $retrySettings,
        int $timeoutMillis
    ) {
        $maxTimeoutMillis = $retrySettings->getMaxRpcTimeoutMillis();
        $timeoutMult = $retrySettings->getRpcTimeoutMultiplier();
        $deadlineMillis = $retrySettings->getDeadlineMillis();
        $currentTime = $this->retrier->getCurrentTimeMillis();

        return (int) min(
            $timeoutMillis * $timeoutMult,
            $maxTimeoutMillis,
            $deadlineMillis - $currentTime
        );
    }
}
