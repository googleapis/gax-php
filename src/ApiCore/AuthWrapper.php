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
        return 'Bearer ' . $this->getToken();
    }

    private function getToken()
    {
        $token = $this->fetchAuthTokenInterface->getLastReceivedToken();
        if ($this->isExpired($token)) {
            $token = $this->fetchAuthTokenInterface->fetchAuthToken($this->authHttpHandler);
        }
        if ($this->isValid($token)) {
            return $token['access_token'];
        } else {
            return '';
        }
    }

    private function isValid($token)
    {
        return !is_null($token)
            && is_array($token)
            && array_key_exists('access_token', $token);
    }

    private function isExpired($token)
    {
        return !($this->isValid($token)
            && array_key_exists('expires_at', $token)
            && $token['expires_at'] > time());
    }
}
