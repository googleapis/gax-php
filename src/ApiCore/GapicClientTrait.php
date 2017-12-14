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

use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\Transport\ApiTransportInterface;
use Google\Cloud\Version;
use Google\Protobuf\Internal\Message;

/**
 * Common functions used to work with various clients.
 */
trait GapicClientTrait
{
    use ArrayTrait;
    use ValidationTrait;

    private static $gapicVersion;

    private $defaultCallSettings;
    private $descriptors;
    private $transport;

    private static function getGapicVersion()
    {
        if (!self::$gapicVersion) {
            if (file_exists(__DIR__.'/../VERSION')) {
                self::$gapicVersion = trim(file_get_contents(__DIR__ . '/../VERSION'));
            } elseif (class_exists(Version::class)) {
                self::$gapicVersion = Version::VERSION;
            }
        }

        return self::$gapicVersion;
    }

    private function setClientOptions(array $options)
    {
        $this->validateNotNull($options, [
            'serviceName',
            'descriptorsConfigPath',
            'clientConfigPath'
        ]);
        if (!isset($options['gapicVersion'])) {
            $options['gapicVersion'] = isset($options['libVersion'])
                ? $options['libVersion']
                : self::getGapicVersion();
        }
        $transport = isset($options['transport'])
            ? $options['transport']
            : null;
        $clientConfig = json_decode(
            file_get_contents($options['clientConfigPath']),
            true
        );
        $this->defaultCallSettings = CallSettings::load(
            $options['serviceName'],
            $clientConfig,
            $this->pluck('retryingOverride', $options, false)
        );
        $descriptors = require($options['descriptorsConfigPath']);
        $this->descriptors = $descriptors['interfaces'][$options['serviceName']];
        $this->transport = $transport instanceof ApiTransportInterface
            ? $transport
            : TransportFactory::build($options);
    }

    private function configureCallSettings($method, array $optionalArgs)
    {
        $defaultCallSettings = $this->defaultCallSettings[$method];
        if (isset($optionalArgs['retrySettings']) && is_array($optionalArgs['retrySettings'])) {
            $optionalArgs['retrySettings'] = $defaultCallSettings->getRetrySettings()->with(
                $optionalArgs['retrySettings']
            );
        }

        return $defaultCallSettings->merge(new CallSettings($optionalArgs));
    }

    /**
     * @param Call $call
     * @param CallSettings $settings
     *
     * @return PromiseInterface
     */
    private function startCall(Call $call, CallSettings $settings)
    {
        $callable = $this->transport->getCallable($settings);
        return $callable($call, $settings);
    }

    /**
     * @param Call $call
     * @param CallSettings $settings
     * @param OperationsGapicClient $client
     * @param array $descriptor
     *
     * @return PromiseInterface
     */
    private function startOperationsCall(
        Call $call,
        CallSettings $settings,
        OperationsClient $client,
        array $descriptor
    ) {
        return $this->startCall($call, $settings)
            ->then(function (Message $response) use ($client, $descriptor) {
                $options = $descriptor + [
                    'lastProtoResponse' => $response
                ];

                return new OperationResponse($response->getName(), $client, $options);
            });
    }

    /**
     * @param Call $call
     * @param CallSettings $settings
     * @param array $descriptor
     *
     * @return PagedListResponse
     */
    private function getPagedListResponse(Call $call, CallSettings $settings, array $descriptor)
    {
        return new PagedListResponse(
            $call,
            $settings,
            $this->transport->getCallable($settings),
            new PageStreamingDescriptor($descriptor)
        );
    }
}
