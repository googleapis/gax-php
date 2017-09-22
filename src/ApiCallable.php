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
namespace Google\GAX;

use Google\GAX\Middleware\CustomHeaderMiddleware;
use Google\GAX\Middleware\LongRunningMiddleware;
use Google\GAX\Middleware\PageStreamingMiddleware;
use Google\GAX\Middleware\RetryMiddleware;
use Google\GAX\Middleware\TimeoutMiddleware;

/**
 * Creates a function wrapper that provides extra functionalities such as retry and bundling.
 */
class ApiCallable
{
    const TRANSPORT_METHOD_PARAM_COUNT = 2;
    const TRANSPORT_METHOD_OPTIONS_INDEX = 1;

    private static function setTimeout($callable, $timeoutMillis)
    {
        return new TimeoutMiddleware($callable, $timeoutMillis);
    }

    private static function setRetry($callable, RetrySettings $retrySettings, $timeFuncMillis)
    {
        return new RetryMiddleware($callable, $retrySettings, $timeFuncMillis);
    }

    private static function setPageStreaming($callable, $pageStreamingDescriptor)
    {
        return new PageStreamingMiddleware($callable, $pageStreamingDescriptor);
    }

    private static function setLongRunnning($callable, $longRunningDescriptor)
    {
        return new LongRunningMiddleware($callable, $longRunningDescriptor);
    }

    private static function setCustomHeader($callable, $headerDescriptor, $userHeaders = null)
    {
        return new CustomHeaderMiddleware($callable, $headerDescriptor, $userHeaders);
    }

    /**
     * @param callable $callable a callable to make API call through.
     * @param \Google\GAX\CallSettings $settings the call settings to use for this call.
     * @param array $options {
     *     Optional.
     *     @type \Google\GAX\PageStreamingDescriptor $pageStreamingDescriptor
     *           the descriptor used for page-streaming.
     *     @type \Google\GAX\AgentHeaderDescriptor $headerDescriptor
     *           the descriptor used for creating GAPIC header.
     * }
     *
     * @throws ValidationException
     * @return callable
     */
    public static function createApiCall(
        TransportInterface $transport,
        $methodName,
        CallSettings $settings,
        $options = []
    ) {
        ApiCallable::validateApiCallSettings($settings, $options);

        $callable = function() use ($transport, $methodName) {
            $callable = [$transport, $methodName];
            return call_user_func_array($callable, func_get_args());
        };

        // Call the sync method "wait" if this is not a gRPC call
        if (!array_key_exists('grpcStreamingDescriptor', $options)) {
            $callable = function() use ($callable) {
                return call_user_func_array($callable, func_get_args())->wait();
            };
        }

        $retrySettings = $settings->getRetrySettings();
        if (!is_null($retrySettings)) {
            if ($retrySettings->retriesEnabled()) {
                $timeFuncMillis = null;
                if (array_key_exists('timeFuncMillis', $options)) {
                    $timeFuncMillis = $options['timeFuncMillis'];
                }
                $callable = self::setRetry($callable, $retrySettings, $timeFuncMillis);
            } elseif ($retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
                $callable = self::setTimeout($callable, $retrySettings->getNoRetriesRpcTimeoutMillis());
            }
        }

        if (array_key_exists('pageStreamingDescriptor', $options)) {
            $callable = self::setPageStreaming($callable, $options['pageStreamingDescriptor']);
        }

        if (array_key_exists('longRunningDescriptor', $options)) {
            $callable = self::setLongRunnning($callable, $options['longRunningDescriptor']);
        }

        if (array_key_exists('headerDescriptor', $options) || !is_null($settings->getUserHeaders())) {
            $callable = self::setCustomHeader($callable, $options['headerDescriptor'], $settings->getUserHeaders());
        }

        return $callable;
    }

    private static function validateApiCallSettings(CallSettings $settings, $options)
    {
        $retrySettings = $settings->getRetrySettings();
        $isGrpcStreaming = array_key_exists('grpcStreamingDescriptor', $options);
        if ($isGrpcStreaming) {
            if (!is_null($retrySettings) && $retrySettings->retriesEnabled()) {
                throw new ValidationException(
                    'grpcStreamingDescriptor not compatible with retry settings'
                );
            }
            if (array_key_exists('pageStreamingDescriptor', $options)) {
                throw new ValidationException(
                    'grpcStreamingDescriptor not compatible with pageStreamingDescriptor'
                );
            }
            if (array_key_exists('longRunningDescriptor', $options)) {
                throw new ValidationException(
                    'grpcStreamingDescriptor not compatible with longRunningDescriptor'
                );
            }
        }
    }
}
