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

use Google\ApiCore\Call;
use Google\ApiCore\ValidationException;

/**
 * Common functions used to work with various clients.
 *
 * @internal
 */
class MethodDescriptor
{
    public function __construct(
        private string $name,
        private int $callType,
        private string $serviceName,
        private ?string $responseType,
        private ?array $headerParams,
        private ?array $longRunning,
        private ?array $pageStreaming,
        private ?array $grpcStreaming,
    ) {
        // Validate various callType specific configurations.
        if ($callType == Call::LONGRUNNING_CALL && is_null($longRunning)) {
            throw new ValidationException("Requested method '$name' does not have a longRunning config " .
                'in descriptor configuration.');
        }
        if ($callType == Call::PAGINATED_CALL && is_null($pageStreaming)) {
            throw new ValidationException("Requested method '$name' with callType PAGINATED_CALL does not " .
                'have a pageStreaming in descriptor configuration.');
        }

        // LRO are either Standard LRO response type or custom, which are handled by
        // startOperationCall, so no need to validate responseType for those callType.
        if ($callType != Call::LONGRUNNING_CALL && is_null($responseType)) {
            throw new ValidationException("Requested method '$name' does not have a responseType " .
                'in descriptor configuration.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallType(): string
    {
        return $this->callType;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function getResponseType(): ?string
    {
        return $this->responseType;
    }

    public function getHeaderParams(): ?array
    {
        return $this->headerParams;
    }

    public function getLongRunning(): ?array
    {
        return $this->longRunning;
    }

    public function getPageStreaming(): ?array
    {
        return $this->pageStreaming;
    }

    public function getGrpcStreaming(): ?array
    {
        return $this->grpcStreaming;
    }

    public function getFullName(): string
    {
        return sprintf('%s/%s', $this->serviceName, $this->name);
    }
}
