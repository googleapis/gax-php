<?php
/*
 * Copyright 2022 Google LLC
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

/**
 * The ApiKeyHeaderCredentials object provides a wrapper around an API key.
 */
class ApiKeyHeaderCredentials implements HeaderCredentialsInterface
{
    private $apiKey;
    private $quotaProject;

    /**
     * ApiKeyHeaderCredentials constructor.
     * @param string $apiKey The API key to set in the header for the request
     * @param string|null $quotaProject The quota project associated with the API key.
     * @throws ValidationException
     */
    public function __construct($apiKey, $quotaProject = null)
    {
        if (empty($apiKey) || !is_string($apiKey)) {
            throw new ValidationException('API key must be a string');
        }
        $this->apiKey = $apiKey;
        $this->quotaProject = $quotaProject;
    }

    /**
     * Factory method to create a CredentialsWrapper from an array of options.
     *
     * @param array $args {
     *     An array of optional arguments.
     *
     *     @type string $apiKey
     *           The API key to set in the header for the request
     *     @type string $quotaProject
     *           Specifies a user project to bill for access charges associated with the request.
     * }
     * @return ApiKeyHeaderCredentials
     * @throws ValidationException
     */
    public static function build(array $args = [])
    {
        $args += [
            'apiKey'        => null,
            'quotaProject'  => null,
        ];

        if (is_null($args['apiKey'])) {
            throw new ValidationException("Cannot build ApiKeyHeaderCredentials without apiKey option");
        }

        return new ApiKeyHeaderCredentials($args['apiKey'], $args['quotaProject']);
    }

    /**
     * @return string|null The quota project associated with the credentials.
     */
    public function getQuotaProject(): ?string
    {
        return $this->quotaProject;
    }

    /**
     * @param string $unusedAudience audiences are not supported for API keys.
     *
     * @return callable Callable function that returns the API key header.
     */
    public function getAuthorizationHeaderCallback($unusedAudience = null): ?callable
    {
        $apiKey = $this->apiKey;

        // NOTE: changes to this function should be treated carefully and tested thoroughly. It will
        // be passed into the gRPC c extension, and changes have the potential to trigger very
        // difficult-to-diagnose segmentation faults.
        return function () use ($apiKey) {
            return ['x-goog-api-key' => [$apiKey]];
        };
    }

    public function checkUniverseDomain(): void
    {
        // no-op, API keys do not have a universe domain
    }
}
