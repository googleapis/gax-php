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
    const DEFAULT_BUFFER_SIZE = 4 * 1024 * 1024; // 4 MB, the maximum size of gRPC message.
    private $buffer;
    private $stream;
    private $decodeType;
    private $ignoreUnknown = true;

    public function __construct(StreamInterface $stream, $decodeType, $options)
    {
        $this->stream = $stream;
        $this->decodeType = $decodeType;

        $bufSize = JsonStreamDecoder::DEFAULT_BUFFER_SIZE;
        if (!is_null($options)) {
            $bufSize = isset($options['bufferSizeBytes']) ? $options['bufferSizeBytes'] : $bufSize;
            $this->ignoreUnknown = isset($options['ignoreUnknown']) ? $options['ignoreUnknown'] : $this->ignoreUnknown;
        }
        $this->buffer = new BufferStream($bufSize);
    }

    public function decode()
    {
        $message = $this->decodeType;
        $open = 0;
        $str = false;
        while (!$this->stream->eof()) {
            $b = $this->stream->read(1);
            // Open/close double quotes of a key or value.
            if ($b === '"') {
                $str = !$str;
            }
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
                // Do not include it in the message buffer.
                if ($open === 1) {
                    continue;
                }
            }
            if (($b === '}' || $b === ']') && !$str) {
                $open--;
                // Closing of the stream array. It is done.
                if ($open === 0) {
                    break;
                }
            }
            $this->buffer->write($b);
            // A message-closing byte was just buffered. Decode the
            // message with the decode type, clearing the buffer,
            // and yield it.
            if ($open === 1) {
                $return = new $message();
                $return->mergeFromJsonString((string)$this->buffer, $this->ignoreUnknown);
                yield $return;
            }
        }
        if ($open !== 0) {
            throw new \Exception("Broken stream");
        }
    }
}
