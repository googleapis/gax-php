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

use Google\GAX\Transport\GrpcTransport;
use Exception;

/**
 * Common functions used to work with various transports.
 */
trait TransportTrait
{
    private $transport;

    private function determineTransport($options)
    {
        if (isset($options['transport'])) {
            if (is_string($options['transport'])) {
                switch ($options['transport']) {
                    case 'grpc':
                        return new GrpcTransport($options);

                    case 'json':
                        throw new Exception('This transport is not yet supported by GAPIC');

                    default:
                        throw new Exception(sprintf(
                            'Invalid transport provided: ',
                            $options['transport']
                        ));
                }
            }

            if ($options['transport'] instanceof TransportInterface) {
                return $options['transport'];
            }

            throw new Exception('Invalid argument provided to transport option');
        }

        // default to Grpc Transport (for now)
        return new GrpcTransport($options);
    }
}
