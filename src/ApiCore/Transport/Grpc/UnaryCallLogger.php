<?php
/*
 * Copyright 2018, Google Inc.
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

namespace Google\ApiCore\Transport\Grpc;

use Grpc\UnaryCall;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * UnaryCallLogger should be extended to allow logging via interceptors.
 */
abstract class UnaryCallLogger
{
    private $logger;
    private $logLevel;
    private $context;

    /**
     * UnaryCallLogger constructor.
     *
     * @param LoggerInterface $logger
     * @param string $logLevel
     * @param array $context
     */
    public function __construct(LoggerInterface $logger, $logLevel = LogLevel::INFO, $context = [])
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->context = $context;
    }

    /**
     * @param $method
     * @param $argument
     * @param array $metadata
     * @param array $options
     */
    public function logRequest(
        $method,
        $argument,
        array $metadata = [],
        array $options = []
    ) {
        $this->logger->log(
            $this->logLevel,
            $this->formatRequest($method, $argument, $metadata, $options),
            $this->context
        );
    }

    /**
     * @param $response
     * @param $status
     * @param UnaryCall|LoggingUnaryCall $unaryCall
     */
    public function logResponse(
        $response,
        $status,
        $unaryCall
    ) {
        $this->logger->log(
            $this->logLevel,
            $this->formatResponse($response, $status, $unaryCall),
            $this->context
        );
    }

    /**
     * @param $method
     * @param $argument
     * @param array $metadata
     * @param array $options
     * @return string
     */
    protected abstract function formatRequest(
        $method,
        $argument,
        array $metadata = [],
        array $options = []);

    /**
     * @param $response
     * @param $status
     * @param UnaryCall $unaryCall
     * @return string
     */
    protected abstract function formatResponse(
        $response,
        $status,
        $unaryCall);
}
