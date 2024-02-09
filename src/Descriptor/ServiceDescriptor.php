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

namespace Google\ApiCore\Descriptor;

use Google\ApiCore\ValidationException;

/**
 * Common functions used to work with various clients.
 *
 * @internal
 */
class ServiceDescriptor
{
    public function __construct(
        private string $serviceName,
        private array $descriptors
    ) {
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function method(string $methodName, string $interfaceName = null): MethodDescriptor
    {
        // Ensure a method descriptor exists for the target method.
        if (!isset($this->descriptors[$methodName])) {
            throw new ValidationException("Requested method '$methodName' does not exist in descriptor configuration.");
        }

        $methodDescriptor = $this->descriptors[$methodName];

        // Ensure required descriptor configuration exists.
        if (!isset($methodDescriptor['callType'])) {
            throw new ValidationException("Requested method '$methodName' does not have a callType " .
                'in descriptor configuration.');
        }

        return new MethodDescriptor(
            $methodName,
            $methodDescriptor['callType'],
            $interfaceName ?? $methodDescriptor['interfaceName'] ?? $this->serviceName,
            $methodDescriptor['responseType'] ?? null,
            $methodDescriptor['headerParams'] ?? null,
            $methodDescriptor['longRunning'] ?? null,
            $methodDescriptor['pageStreaming'] ?? null,
            $methodDescriptor['grpcStreaming'] ?? null
        );
    }

    /**
     * Preserve previous behavior by accessing grpcStreaming without validating
     * the method name
     *
     * @TODO: Remove this behavior
     */
    public function getGrpcStreaming(string $methodName): ?array
    {
        return $this->descriptors[$methodName]['grpcStreaming'] ?? null;
    }
}
