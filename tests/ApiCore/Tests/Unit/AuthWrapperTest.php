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

namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\AuthWrapper;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Cache\MemoryCacheItemPool;
use Google\Auth\Cache\SysVCacheItemPool;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenCache;
use Google\Auth\FetchAuthTokenInterface;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GPBMetadata\Google\Api\Auth;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class AuthWrapperTest extends TestCase
{
    /**
     * @dataProvider buildData
     */
    public function testBuild($args, $expectedAuthWrapper)
    {
        $actualAuthWrapper = AuthWrapper::build($args);
        $this->assertEquals($expectedAuthWrapper, $actualAuthWrapper);
    }

    public function buildData()
    {
        $scopes = ['myscope'];
        $httpHandler = HttpHandlerFactory::build();
        $authHttpHandler = function ($request, $options) use ($httpHandler) {
            return $httpHandler->async($request, $options)->wait();
        };
        $defaultAuthCache = new MemoryCacheItemPool();
        $authCache = new SysVCacheItemPool();
        $authCacheOptions = ['lifetime' => 600];
        return [
            [
                [],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials(null, null, null, $defaultAuthCache)),
            ],
            [
                ['scopes' => $scopes],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials($scopes, null, null, $defaultAuthCache)),
            ],
            [
                ['scopes' => $scopes, 'authHttpHandler' => $authHttpHandler],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials($scopes, null, null, $defaultAuthCache), $authHttpHandler),
            ],
            [
                ['enableCaching' => false],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials(null, null, null, null)),
            ],
            [
                ['authCacheOptions' => $authCacheOptions],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials(null, null, $authCacheOptions, $defaultAuthCache)),
            ],
            [
                ['authCache' => $authCache],
                new AuthWrapper(ApplicationDefaultCredentials::getCredentials(null, null, null, $authCache)),
            ],
        ];
    }

    /**
     * @dataProvider fromKeyFileData
     */
    public function testFromKeyFile($keyFile, $args, $expectedAuthWrapper)
    {
        $actualAuthWrapper = AuthWrapper::fromKeyFile($keyFile, $args);
        $this->assertEquals($expectedAuthWrapper, $actualAuthWrapper);
    }

    public function fromKeyFileData()
    {
        $keyFilePath = __DIR__ . '/testdata/json-key-file.json';
        $keyFile = json_decode(file_get_contents($keyFilePath), true);

        $scopes = ['myscope'];
        $authHttpHandler = function () {};
        $defaultAuthCache = new MemoryCacheItemPool();
        $authCache = new SysVCacheItemPool();
        $authCacheOptions = ['lifetime' => 600];
        return [
            [
                $keyFile,
                [],
                $this->makeExpectedKeyFileCreds($keyFile, null, $defaultAuthCache, null, null),
            ],
            [
                $keyFilePath,
                [],
                $this->makeExpectedKeyFileCreds($keyFile, null, $defaultAuthCache, null, null),
            ],
            [
                $keyFile,
                ['scopes' => $scopes],
                $this->makeExpectedKeyFileCreds($keyFile, $scopes, $defaultAuthCache, null, null),
            ],
            [
                $keyFile,
                ['scopes' => $scopes, 'authHttpHandler' => $authHttpHandler],
                $this->makeExpectedKeyFileCreds($keyFile, $scopes, $defaultAuthCache, null, $authHttpHandler),
            ],
            [
                $keyFile,
                ['enableCaching' => false],
                $this->makeExpectedKeyFileCreds($keyFile, null, null, null, null),
            ],
            [
                $keyFile,
                ['authCacheOptions' => $authCacheOptions],
                $this->makeExpectedKeyFileCreds($keyFile, null, $defaultAuthCache, $authCacheOptions, null),
            ],
            [
                $keyFile,
                ['authCache' => $authCache],
                $this->makeExpectedKeyFileCreds($keyFile, null, $authCache, null, null),
            ],
        ];
    }

    private function makeExpectedKeyFileCreds($keyFile, $scopes, $cache, $cacheConfig, $httpHandler)
    {
        $loader = CredentialsLoader::makeCredentials($scopes, $keyFile);
        if ($cache) {
            $loader = new FetchAuthTokenCache($loader, $cacheConfig, $cache);
        }
        return new AuthWrapper($loader, $httpHandler);
    }

    /**
     * @dataProvider getBearerStringData
     */
    public function testGetBearerString($fetcher, $expectedBearerString)
    {
        $authWrapper = new AuthWrapper($fetcher);
        $bearerString = $authWrapper->getBearerString();
        $this->assertSame($expectedBearerString, $bearerString);
    }

    public function getBearerStringData()
    {
        $expiredFetcher = $this->prophesize(FetchAuthTokenInterface::class);
        $expiredFetcher->getLastReceivedToken()
            ->willReturn([
                'access_token' => 123,
                'expires_at' => time() - 1
            ]);
        $expiredFetcher->fetchAuthToken(Argument::any())
            ->willReturn([
                'access_token' => 456,
                'expires_at' => time() + 1000
            ]);
        $unexpiredFetcher = $this->prophesize(FetchAuthTokenInterface::class);
        $unexpiredFetcher->getLastReceivedToken()
            ->willReturn([
                'access_token' => 123,
                'expires_at' => time() + 100,
            ]);
        return [
            [$expiredFetcher->reveal(), 'Bearer 456'],
            [$unexpiredFetcher->reveal(), 'Bearer 123'],
        ];
    }

    /**
     * @dataProvider getAuthorizationHeaderCallbackData
     */
    public function testGetAuthorizationHeaderCallback($fetcher, $expectedCallbackResponse)
    {
        $authWrapper = new AuthWrapper($fetcher);
        $callback = $authWrapper->getAuthorizationHeaderCallback();
        $actualResponse = $callback();
        $this->assertSame($expectedCallbackResponse, $actualResponse);
    }

    public function getAuthorizationHeaderCallbackData()
    {
        $expiredFetcher = $this->prophesize(FetchAuthTokenInterface::class);
        $expiredFetcher->getLastReceivedToken()
            ->willReturn([
                'access_token' => 123,
                'expires_at' => time() - 1
            ]);
        $expiredFetcher->fetchAuthToken(Argument::any())
            ->willReturn([
                'access_token' => 456,
                'expires_at' => time() + 1000
            ]);
        $unexpiredFetcher = $this->prophesize(FetchAuthTokenInterface::class);
        $unexpiredFetcher->getLastReceivedToken()
            ->willReturn([
                'access_token' => 123,
                'expires_at' => time() + 100,
            ]);
        return [
            [$expiredFetcher->reveal(), ['authorization' => ['Bearer 456']]],
            [$unexpiredFetcher->reveal(), ['authorization' => ['Bearer 123']]],
        ];
    }
}
