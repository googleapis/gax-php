<?php
/*
 * Copyright 2017 Google LLC
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
 * @internal
 */
trait ValidationTrait
{
    /**
     * @param array $arr Associative array
     * @param array $requiredKeys List of keys to check for in $arr
     * @return array Returns $arr for fluent use
     */
    public static function validate(array $arr, array $requiredKeys)
    {
        return self::validateImpl($arr, $requiredKeys, true);
    }

    /**
     * @param array $arr Associative array
     * @param array $requiredKeys List of keys to check for in $arr
     * @return array Returns $arr for fluent use
     */
    public static function validateNotNull(array $arr, array $requiredKeys)
    {
        return self::validateImpl($arr, $requiredKeys, false);
    }

    /**
     * @param array $arr Associative array
     * @param array $requiredKeys1 List of keys to check for in $arr
     * @param array $requiredKeys2 List of keys to check for in $arr
     * @return array Returns $arr for fluent use
     * @throws ValidationException
     */
    public static function validateAllKeysFromOneOf(array $arr, array $requiredKeys1, array $requiredKeys2)
    {
        // Check if all keys in requiredKeys1 are present in arr
        $allKeys1Present = count(array_diff($requiredKeys1, array_keys($arr))) === 0;
        // Check if all keys in requiredKeys2 are present in arr
        $allKeys2Present = count(array_diff($requiredKeys2, array_keys($arr))) === 0;
        // Return $arr if either all keys from requiredKeys1 or requiredKeys2 are present
        if ($allKeys1Present || $allKeys2Present) {
            return $arr;
        }
        throw new ValidationException("Missing required arguments");
    }

    private static function validateImpl($arr, $requiredKeys, $allowNull)
    {
        foreach ($requiredKeys as $requiredKey) {
            $valid = array_key_exists($requiredKey, $arr)
                && ($allowNull || !is_null($arr[$requiredKey]));
            if (!$valid) {
                throw new ValidationException("Missing required argument $requiredKey");
            }
        }
        return $arr;
    }

    /**
     * @param string $filePath
     * @throws ValidationException
     */
    private static function validateFileExists(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new ValidationException("Could not find specified file: $filePath");
        }
    }
}
