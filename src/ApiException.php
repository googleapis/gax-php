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

use Exception;

class ApiException extends Exception
{
    private $metadata;

    public function __construct($message, $code, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param \stdClass $status
     * @return ApiException
     */
    public static function createFromStdClass($status)
    {
        $ex = new ApiException($status->details, $status->code);
        if (property_exists($status, 'metadata')) {
            $ex->metadata = $status->metadata;
        }
        return $ex;
    }

    /**
     * @return mixed[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return string
     */
    public function getMetadataAsString()
    {
        $decodedMetadata = Serializer::decodeMetadata($this->metadata);
        return json_encode($decodedMetadata);
    }

    /**
     * String representation of ApiException
     * @return string
     */
    public function __toString()
    {
        $str = __CLASS__ . ": [{$this->code}]: {$this->message}\n";
        if (isset($this->metadata) && count($this->metadata) > 0) {
            $str .= "Metadata: " . $this->getMetadataAsString();
        }
        return $str;
    }


}
