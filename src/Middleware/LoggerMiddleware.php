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

namespace Google\ApiCore\Middleware;

use Google\ApiCore\Call;
use Psr\Log\LoggerInterface;

 /**
  * Middleware that logs request information
  */
  class LoggerMiddleware implements MiddlewareInterface
  {
    /** @var callable */
    private $nextHandler;
    private LoggerInterface $logger;
    private string $serviceName;

    public function __construct(
        callable $nextHandler, 
        LoggerInterface $logger,
        string $serviceName
    )
    {
        $this->nextHandler = $nextHandler;
        $this->logger = $logger;
        $this->serviceName = $serviceName;
    }

    public function __invoke(Call $call, array $options)
    {
        $nextHandler = $this->nextHandler;
        $startTime = date(DATE_RFC3339);

        $infoEvent = [
            'timestamp' => $startTime,
            'severity' => 'INFO',
            'jsonPayload' => [
                'serviceName' => $this->serviceName
            ]
        ];
        
        // If retry is set and is bigger than 0, we add it to the log.
        if ($options['retryAttempt']) {
            $infoEvent['jsonPayload']['retryAttempt'] = $options['retryAttempt'];
        }

        $this->logger->info(json_encode($infoEvent));

        return $nextHandler($call, $options)->then(function($response) use ($startTime) {
            $endTime = date(DATE_RFC3339);

            $debugEvent = [
                'timestamp' => $startTime,
                'severity' => 'DEBUG',
                'jsonPayload' => [
                    'latency' => strtotime($endTime) - strtotime($endTime)
                ]
            ];

            $this->logger->debug(json_encode($debugEvent));

            return $response;
        }, function($exception) use ($startTime) {
            $endTime = date(DATE_RFC3339);

            $debugEvent = [
                'timestamp' => $startTime,
                'severity' => 'DEBUG',
                'jsonPayload' => [
                    'latency' => strtotime($endTime) - strtotime($endTime)
                ]
            ];

            $this->logger->debug(json_encode($debugEvent));

            throw $exception;
        });
    }
  }
