<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\ApiCore;

use Exception;
use InvalidArgumentException;
use Google\ApiCore\LongRunning\OperationsClient;
use Google\Cloud\Version;

/**
 * Common functions used to work with various clients.
 */
trait GapicClientTrait
{
    use ArrayTrait;

    private static $gapicVersion;

    private $defaultCallSettings;
    private $descriptors;
    private $scopes;
    private $transport;

    /**
     * Get either a gRPC or REST transport based on the provided config
     * and the system dependencies available.
     *
     * @param array $config
     * @return ApiTransportInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function getTransport(array $config)
    {
        $serviceAddress = $this->pluck('serviceAddress', $config);
        $port = $this->pluck('port', $config);
        $isGrpcExtensionLoaded = $this->getGrpcDependencyStatus();
        $defaultTransport = $isGrpcExtensionLoaded
            ? 'grpc'
            : 'rest';
        $transport = isset($config['transport'])
            ? strtolower($config['transport'])
            : $defaultTransport;

        if ($transport === 'grpc' && !$isGrpcExtensionLoaded) {
            throw new Exception(
                'gRPC support has been requested but required dependencies ' .
                'have not been found. Please make sure to run the following ' .
                'from the command line: pecl install grpc'
            );
        }

        switch ($transport) {
            case 'grpc':
                $transport = GrpcTransport::class;
                break;
            case 'rest':
                $transport = RestTransport::class;
                break;
            default:
                throw new InvalidArgumentException('Unknown transport type.');
        }

        return new $transport("$serviceAddress:$port", $config);
    }

    private static function getGapicVersion()
    {
        if (!self::$gapicVersion) {
            if (file_exists(__DIR__.'/../VERSION')) {
                self::$gapicVersion = trim(file_get_contents(__DIR__.'/../VERSION'));
            } elseif (class_exists(Version::class)) {
                self::$gapicVersion = Version::VERSION;
            }
        }

        return self::$gapicVersion;
    }

    private function configureClient($serviceName, $descriptorsPath, array $options)
    {
        $options += [
            'retryingOverride' => [],
            'libName' => null,
            'libVersion' => self::getGapicVersion()
        ];
        $clientConfigJsonString = file_get_contents($options['clientConfigPath']);
        $clientConfig = json_decode($clientConfigJsonString, true);
        $this->defaultCallSettings = CallSettings::load(
            $serviceName,
            $clientConfig,
            $options['retryingOverride']
        );
        $this->descriptors = require($descriptorsPath);
        $this->transport = $this->getTransport($options);

        return $options;
    }

    private function configureOperationsClient(array $options)
    {
        if (array_key_exists('operationsClient', $options)) {
            return $options['operationsClient'];
        }

        $operationsClientOptions = $options;
        unset($operationsClientOptions['retryingOverride']);
        unset($operationsClientOptions['clientConfigPath']);

        return new OperationsClient($operationsClientOptions);
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
     * Abstract the checking of the grpc extension for unit testing.
     *
     * @codeCoverageIgnore
     * @return bool
     */
    protected function getGrpcDependencyStatus()
    {
        return extension_loaded('grpc');
    }
}
