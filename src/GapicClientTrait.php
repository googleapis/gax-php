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
    public function determineTransport($options)
    {
        if (isset($options['transport'])) {
            switch ($options['transport']) {
                case 'grpc':
                    return 'grpc';

                default:
                    throw new Exception(sprintf(
                        'Invalid transport provided: ',
                        $options['transport']
                    ));
            }
        }

        // @TODO Logic to determine the default transport will go here
        return 'grpc'
    }
}
