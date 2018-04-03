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
namespace Google\ApiCore;

use Google\Auth\FetchAuthTokenInterface;

/**
 * The AuthWrapper object provides a wrapper around a FetchAuthTokenInterface.
 */
class AuthWrapper
{
    private $fetchAuthTokenInterface;
    private $authHttpHandler;

    /**
     * AuthWrapper constructor.
     * @param FetchAuthTokenInterface $fetchAuthTokenInterface A credentials loader
     *        used to fetch access tokens.
     * @param callable $authHttpHandler A handler used to deliver PSR-7 requests
     *        specifically for authentication. Should match a signature of
     *        `function (RequestInterface $request, array $options) : ResponseInterface`.
     */
    public function __construct(FetchAuthTokenInterface $fetchAuthTokenInterface, callable $authHttpHandler = null)
    {
        $this->fetchAuthTokenInterface = $fetchAuthTokenInterface;
        $this->authHttpHandler = $authHttpHandler;
    }

    /**
     * @return string Bearer string containing access token.
     */
    public function getBearerString()
    {
        return 'Bearer ' . self::getToken($this->fetchAuthTokenInterface, $this->authHttpHandler);
    }

    /**
     * @return callable Callable function that returns an authorization header.
     */
    public function getAuthorizationHeaderCallback()
    {
        $fetchAuthTokenInterface = $this->fetchAuthTokenInterface;
        $authHttpHandler = $this->authHttpHandler;

        // NOTE: changes to this function should be treated carefully and tested thoroughly. It will
        // be passed into the gRPC c extension, and changes have the potential to trigger very
        // difficult-to-diagnose segmentation faults.
        return function () use ($fetchAuthTokenInterface, $authHttpHandler) {
            return ['authorization' => ['Bearer ' . self::getToken($fetchAuthTokenInterface, $authHttpHandler)]];
        };
    }

    private static function getToken($fetchAuthTokenInterface, $authHttpHandler)
    {
        $token = $fetchAuthTokenInterface->getLastReceivedToken();
        if (self::isExpired($token)) {
            $token = $fetchAuthTokenInterface->fetchAuthToken($authHttpHandler);
            if (!self::isValid($token)) {
                return '';
            }
        }
        return $token['access_token'];
    }

    private static function isValid($token)
    {
        return is_array($token)
            && array_key_exists('access_token', $token);
    }

    private static function isExpired($token)
    {
        return !(self::isValid($token)
            && array_key_exists('expires_at', $token)
            && $token['expires_at'] > time());
    }
}
