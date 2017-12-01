<?php
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
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

namespace Google\GAX;

use Exception;

/**
 * Common functions used to work with various transports.
 */
trait GapicClientTrait
{
    /**
     * Get either a gRPC or REST connection based on the provided config
     * and the system dependencies available.
     *
     * @param array $config
     * @return string
     * @throws Exception
     */
    private function getTransport(array $config)
    {
        $isGrpcExtensionLoaded = $this->getGrpcDependencyStatus();
        // @todo add rest support
        // $defaultTransport = $isGrpcExtensionLoaded ? 'grpc' : 'rest';
        $defaultTransport = 'grpc';
        $transport = isset($config['transport'])
            ? strtolower($config['transport'])
            : $defaultTransport;

        if ($transport === 'grpc') {
            if (!$isGrpcExtensionLoaded) {
                throw new Exception(
                    'gRPC support has been requested but required dependencies ' .
                    'have not been found. Please make sure to run the following ' .
                    'from the command line: pecl install grpc'
                );
            }
        }

        return $transport;
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
