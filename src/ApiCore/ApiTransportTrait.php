<?php
/*
 * Copyright 2017, Google Inc.
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

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Cache\MemoryCacheItemPool;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenCache;
use Google\Auth\HttpHandler\HttpHandlerFactory;

trait ApiTransportTrait
{
    use ArrayTrait;
    use ValidationTrait;

    private $agentHeaderDescriptor;

    private function setCommonDefaults(array $options)
    {
        $options += [
            'enableCaching' => true,
            'authCache' => new MemoryCacheItemPool(),
            'authCacheOptions' => null,
            'authHttpHandler' => HttpHandlerFactory::build(),
            'libName' => null,
            'libVersion' => null,
            'gapicVersion' => null,
        ];

        if (empty($options['credentialsLoader'])) {
            $this->validateNotNull($options, ['scopes']);
            $options['credentialsLoader'] = $this->getADCCredentials(
                $options['scopes'],
                $options['authHttpHandler']
            );
        }

        if ($options['enableCaching']) {
            $options['credentialsLoader'] = new FetchAuthTokenCache(
                $options['credentialsLoader'],
                $options['authCacheOptions'],
                $options['authCache']
            );
        }

        $this->agentHeaderDescriptor = new AgentHeaderDescriptor([
            'libName' => $options['libName'],
            'libVersion' => $options['libVersion'],
            'gapicVersion' => $options['libVersion']
        ]);

        return $options;
    }

    /**
     * @param Call $call
     * @param CallSettings $settings The call settings to use for this call.
     *
     * @return PromiseInterface
     */
    public function startCall(Call $call, CallSettings $settings)
    {
        $callable = $this->getCallable($settings);
        return $callable($call, $settings);
    }

    /**
     * @param Call $call
     * @param CallSettings $settings The call settings to use for this call.
     * @param PageStreamingDescriptor $descriptor
     *
     * @return PromiseInterface
     */
    public function getPagedListResponse(Call $call, CallSettings $settings, PageStreamingDescriptor $descriptor)
    {
        return new PagedListResponse(
            $call,
            $settings,
            $this->getCallable($settings),
            $descriptor
        );
    }

    /**
     * @param callable $callable A callable to make the API call through.
     * @param CallSettings $settings The call settings to use for this call.
     *
     * @return callable
     */
    private function createCallStack(
        callable $callable,
        CallSettings $settings
    ) {
        if ($this->agentHeaderDescriptor) {
            $callable = new Middleware\AgentHeaderMiddleware($callable, $this->agentHeaderDescriptor);
        }

        $callable = new Middleware\RetryMiddleware($callable);

        return $callable;
    }

    /**
     * Gets credentials from ADC. This exists to allow overriding in unit tests.
     *
     * @param string[] $scopes
     * @param callable $httpHandler
     * @return CredentialsLoader
     */
    protected function getADCCredentials(array $scopes, callable $httpHandler)
    {
        return ApplicationDefaultCredentials::getCredentials($scopes, $httpHandler);
    }
}
