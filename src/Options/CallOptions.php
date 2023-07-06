<?php
/*
 * Copyright 2023 Google LLC
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

namespace Google\ApiCore\Options;

use ArrayAccess;
use Google\ApiCore\RetrySettings;
use Google\ApiCore\TransportInterface;

/**
 * The CallOptions class provides typing to the associative array of options
 * passed to transport RPC methods. See {@see TransportInterface::startUnaryCall()},
 * {@see TransportInterface::startBidiStreamingCall()},
 * {@see TransportInterface::startClientStreamingCall()}, and
 * {@see TransportInterface::startServerStreamingCall()}.
 */
class CallOptions implements ArrayAccess
{
    use OptionsTrait;

    private array $headers;
    private ?int $timeoutMillis;
    private array $transportOptions;

    /** @var RetrySettings|array|null $retrySettings */
    private $retrySettings;

    /**
     * @param array $options {
     *     Call options
     *
     *     @type array $headers
     *           Key-value array containing headers
     *     @type int $timeoutMillis
     *           The timeout in milliseconds for the call.
     *     @type array $transportOptions
     *           Transport-specific call options. See {@see CallOptions::setTransportOptions}.
     *     @type RetrySettings|array|null $retrySettings
     *           A retry settings override for the call.
     * }
     */
    public function __construct(array $options)
    {
        $this->fromArray($options);
    }

    /**
     * Sets the array of options as class properites.
     *
     * @param array $arr See the constructor for the list of supported options.
     */
    private function fromArray(array $arr): void
    {
        $this->setHeaders($arr['headers'] ?? []);
        $this->setTimeoutMillis($arr['timeoutMillis'] ?? null);
        $this->setTransportOptions($arr['transportOptions'] ?? []);
        $this->setRetrySettings($arr['retrySettings'] ?? null);
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param int|null $timeoutMillis
     */
    public function setTimeoutMillis(?int $timeoutMillis)
    {
        $this->timeoutMillis = $timeoutMillis;
    }

    /**
     * @param array $transportOptions {
     *     Transport-specific call-time options.
     *
     *     @type array $grpcOptions
     *           Key-value pairs for gRPC-specific options which will be passed as the `$options`
     *           argument to the {@see \Grpc\BaseStub} request methods.
     *           See {@link https://grpc.github.io/grpc/core/group__grpc__arg__keys.html} and
     *           {@link https://grpc.github.io/grpc/php/class_grpc_1_1_base_stub.html}.
     *     @type array $grpcFallbackOptions
     *           Key-value pairs for gRPC fallback specific options which will be passed as the
     *           `$options` argument of the `$httpHandler` callable. By
     *           default these will be passed to {@see \GuzzleHttp\Client} as request options.
     *           See {@link https://docs.guzzlephp.org/en/stable/request-options.html}.
     *     @type array $restOptions
     *           Key-value pairs for REST-specific options which will be passed as the `$options`
     *           argument of the `$httpHandler` callable. By default, these will be passed to
     *           {@see \GuzzleHttp\Client} as request options.
     *           See {@link https://docs.guzzlephp.org/en/stable/request-options.html}.
     * }
     */
    public function setTransportOptions(array $transportOptions)
    {
        $this->transportOptions = $transportOptions;
    }

    /**
     * @param RetrySettings|array|null $retrySettings
     */
    public function setRetrySettings($retrySettings)
    {
        $this->retrySettings = $retrySettings;
    }
}
