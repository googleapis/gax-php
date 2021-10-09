<?php
/*
 * Copyright 2021 Google LLC
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

namespace Google\ApiCore\Transport\Rest;

use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\StreamInterface;

class JsonStreamDecoder
{
    private $messageBuffer;
    private $stream;
    private $decodeType;
    private $ignoreUnknown = true;
    private $readChunkSize = 1024;

    public function __construct(StreamInterface $stream, $decodeType, $options = [])
    {
        $this->stream = $stream;
        $this->decodeType = $decodeType;

        $bufSize = 4 * 1024 * 1024; // 4 MB, the maximum size of gRPC message.
        if (!is_null($options)) {
            $bufSize = isset($options['bufferSizeBytes']) ? $options['bufferSizeBytes'] : $bufSize;
            $this->ignoreUnknown = isset($options['ignoreUnknown']) ? $options['ignoreUnknown'] : $this->ignoreUnknown;
            $this->readChunkSize = isset($options['readChunkSize']) ? $options['readChunkSize'] : $this->readChunkSize;
        }
        $this->messageBuffer = new BufferStream($bufSize);
    }

    public function decode()
    {
        $message = $this->decodeType;
        $open = 0;
        $str = false;
        $escaped = false;
        while (!$this->stream->eof()) {
            $chunk = $this->stream->read($this->readChunkSize);
            
            foreach (str_split($chunk) as $b) {
                if ($b === '\\') {
                    $escaped = true;
                }
                // Open/close double quotes of a key or value.
                if ($b === '"') {
                    if (!$escaped) {
                        $str = !$str;
                    }
                }
                // Disable escaped flag after potentially processing
                // a double quote, the only character that needs handling.
                $escaped = false;

                // Blank space between messages.
                if ($b === '' || (ctype_space($b) && !$str)) {
                    continue;
                }
                // Commas separating messages in the stream array.
                if ($b === ',' && $open === 1) {
                    continue;
                }
                if (($b === '{' || $b === '[') && !$str) {
                    $open++;
                    // Opening of the array/root object.
                    // Do not include it in the message messageBuffer$messageBuffer.
                    if ($open === 1) {
                        continue;
                    }
                }
                if (($b === '}' || $b === ']') && !$str) {
                    $open--;
                    // Closing of the stream array. It is done.
                    if ($open === 0) {
                        return;
                    }
                }
                $this->messageBuffer->write($b);
                // A message-closing byte was just buffered. Decode the
                // message with the decode type, clearing the messageBuffer$messageBuffer,
                // and yield it.
                if ($open === 1) {
                    $return = new $message();
                    $return->mergeFromJsonString((string)$this->messageBuffer, $this->ignoreUnknown);
                    yield $return;
                }
            }
        }
        if ($open !== 0) {
            throw new \Exception("Stream closed before receiving the closing byte");
        }
    }
}
