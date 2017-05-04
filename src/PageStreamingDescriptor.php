<?php
/*
 * Copyright 2016, Google Inc.
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
namespace Google\GAX;

use InvalidArgumentException;

/**
 * Holds the description information used for page streaming.
 */
class PageStreamingDescriptor
{
    private $descriptor;

    public function __construct($descriptor)
    {
        self::validate($descriptor);
        $this->descriptor = $descriptor;

    }

    public static function createFromFields($fields)
    {
        $requestPageToken = $fields['requestPageTokenField'];
        $responsePageToken = $fields['responsePageTokenField'];
        $resources = $fields['resourceField'];

        $descriptor = [
            'requestPageTokenGetMethod' => PageStreamingDescriptor::getMethod($requestPageToken),
            'requestPageTokenSetMethod' => PageStreamingDescriptor::setMethod($requestPageToken),
            'responsePageTokenGetMethod' => PageStreamingDescriptor::getMethod($responsePageToken),
            'responsePageTokenSetMethod' => PageStreamingDescriptor::setMethod($responsePageToken),
            'resourcesGetMethod' => PageStreamingDescriptor::getMethod($resources),
            'resourcesSetMethod' => PageStreamingDescriptor::setMethod($resources),
        ];

        if (isset($fields['requestPageSizeField'])) {
            $requestPageSize = $fields['requestPageSizeField'];
            $descriptor['requestPageSizeGetMethod'] = PageStreamingDescriptor::getMethod($requestPageSize);
            $descriptor['requestPageSizeSetMethod'] = PageStreamingDescriptor::setMethod($requestPageSize);
        }

        return new PageStreamingDescriptor($descriptor);
    }

    private static function getMethod($field)
    {
        return 'get' . ucfirst($field);
    }

    private static function setMethod($field)
    {
        return 'set' . ucfirst($field);
    }

    public function getRequestPageTokenGetMethod()
    {
        return $this->descriptor['requestPageTokenGetMethod'];
    }

    public function getRequestPageSizeGetMethod()
    {
        return $this->descriptor['requestPageSizeGetMethod'];
    }

    public function requestHasPageSizeField()
    {
        return array_key_exists('requestPageSizeGetMethod', $this->descriptor);
    }

    public function getResponsePageTokenGetMethod()
    {
        return $this->descriptor['responsePageTokenGetMethod'];
    }

    public function getResourcesGetMethod()
    {
        return $this->descriptor['resourcesGetMethod'];
    }

    public function getRequestPageTokenSetMethod()
    {
        return $this->descriptor['requestPageTokenSetMethod'];
    }

    public function getRequestPageSizeSetMethod()
    {
        return $this->descriptor['requestPageSizeSetMethod'];
    }

    public function getResponsePageTokenSetMethod()
    {
        return $this->descriptor['responsePageTokenSetMethod'];
    }

    public function getResourcesSetMethod()
    {
        return $this->descriptor['resourcesSetMethod'];
    }

    private static function validate($descriptor)
    {
        $requiredFields = [
            'requestPageTokenGetMethod',
            'requestPageTokenSetMethod',
            'responsePageTokenGetMethod',
            'responsePageTokenSetMethod',
            'resourcesGetMethod',
            'resourcesSetMethod',
        ];
        foreach ($requiredFields as $field) {
            if (empty($descriptor[$field])) {
                throw new InvalidArgumentException(
                    "$field is required for PageStreamingDescriptor"
                );
            }
        }
    }
}
