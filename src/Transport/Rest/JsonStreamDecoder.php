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
    const ESCAPE_CHAR = '\\';
    private $messageBuffer;
    private $stream;
    private $decodeType;
    private $ignoreUnknown = true;
    private $readChunkSize = 1024;
    private $messageBufferSize = 4 * 1024 * 1024; // 4 MB, the maximum size of gRPC message.

    public function __construct(StreamInterface $stream, $decodeType, $options = [])
    {
        $this->stream = $stream;
        $this->decodeType = $decodeType;

        if (!is_null($options)) {
            $this->messageBufferSize = isset($options['bufferSizeBytes']) ? $options['bufferSizeBytes'] : $this->messageBufferSize;
            $this->ignoreUnknown = isset($options['ignoreUnknown']) ? $options['ignoreUnknown'] : $this->ignoreUnknown;
            $this->readChunkSize = isset($options['readChunkSize']) ? $options['readChunkSize'] : $this->readChunkSize;
        }
        $this->messageBuffer = new BufferStream($this->messageBufferSize);
    }

    public function decode()
    {
        $message = $this->decodeType;
        $open = 0;
        $str = false;
        $prev = '';
        while (!$this->stream->eof()) {
            // Read up to $readChunkSize bytes from the stream.
            $chunk = $this->stream->read($this->readChunkSize);
            
            foreach (str_split($chunk) as $b) {
                // Track open/close double quotes of a key or value. Do not
                // toggle flag with the pervious byte was an escape character.
                if ($b === '"' && $prev !== self::ESCAPE_CHAR) {
                        $str = !$str;
                }

                // Ignore blank space between messages. Essentially minifies the
                // JSON data.
                if ($b === '' || (ctype_space($b) && !$str)) {
                    $prev = $b;
                    continue;
                }
                // Ignore commas separating messages in the stream array.
                if ($b === ',' && $open === 1) {
                    $prev = $b;
                    continue;
                }
                // Track the opening of a new array or object if not in a string
                // value.
                if (($b === '{' || $b === '[') && !$str) {
                    $open++;
                    // Opening of the array/root object.
                    // Do not include it in the messageBuffer.
                    if ($open === 1) {
                        $prev = $b;
                        continue;
                    }
                }
                // Track the closing of an array or object if not in a string
                // value.
                if (($b === '}' || $b === ']') && !$str) {
                    $open--;
                }
                $this->messageBuffer->write($b);
                $prev = $b;

                // A message-closing byte was just buffered. Decode the
                // message with the decode type, clearing the messageBuffer,
                // and yield it.
                //
                // TODO(noahdietz): Support google.protobuf.*Value messages that
                // are encoded as primitives and separated by commas.
                if ($open === 1) {
                    $json = (string)$this->messageBuffer;
                    $return = new $message();
                    $return->mergeFromJsonString($json, $this->ignoreUnknown);
                    yield $return;
                }
            }
        }
        if ($open !== 0) {
            throw new \Exception('Unexpected stream close before receiving the closing byte');
        }
    }
}
