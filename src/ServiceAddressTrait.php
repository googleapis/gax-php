<?php
/*
 * Copyright 2018 Google LLC
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
 * Provides helper methods for service address handling.
 */
trait ServiceAddressTrait
{
    private static $defaultPort = 443;

    /**
     * @param string $serviceAddress
     * @return array
     * @throws ValidationException
     */
    private static function normalizeServiceAddress($serviceAddress)
    {
        // Check if ipv6 address with port.
        if (preg_match('/\[(\S{1,})\]\:(\d{1,})/', $serviceAddress, $parts) === 1) {
            return [$parts[1], $parts[2]];
        }

        $components = explode(':', $serviceAddress);

        if (count($components) > 2) {
            // IPv6
            return [$serviceAddress, self::$defaultPort];
        } elseif (count($components) == 2) {
            // Port is included in service address
            return [$components[0], $components[1]];
        } elseif (count($components) == 1) {
            // Port is not included - append default port
            return [$components[0], self::$defaultPort];
        }

        throw new ValidationException("Invalid serviceAddress: $serviceAddress");
    }
}
