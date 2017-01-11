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
namespace Google\Gax;

use Grpc;
use Google\GAX\ApiException;
use Google\GAX\ValidationException;

/**
 * ClientStreamingResponse is the response object from a gRPC client streaming API call.
 */
class BidiStreamingResponse
{
    private $call;
    private $isComplete = false;
    private $sentWritesDone = false;

    /**
     * BidiStreamingResponse constructor.
     *
     * @param \Grpc\BidiStreamingCall $bidiStreamingCall The gRPC bidirectional streaming call object
     */
    public function __construct($bidiStreamingCall)
    {
        $this->call = $bidiStreamingCall;
    }

    /**
     * Write data to the server.
     *
     * @param mixed $data The data to write
     * @throws ValidationException
     */
    public function write($data)
    {
        if ($this->sentWritesDone) {
            throw new ValidationException("Cannot call write() after calling writesDone().");
        }
        if ($this->isComplete) {
            throw new ValidationException("Cannot call write() after streaming call is complete.");
        }
        $this->call->write($data);
    }

    /**
     * Write all data in $dataArray.
     *
     * @param mixed[] $dataArray An iterator of data objects to write to the server
     *
     * @throws ValidationException
     * @throws ApiException
     */
    public function writeAll($dataArray = [])
    {
        foreach ($dataArray as $data) {
            $this->write($data);
        }
    }

    /**
     * Inform the server that no more data will be written. The write() function cannot be called
     * after writesDone() is called.
     */
    public function writesDone()
    {
        if ($this->isComplete) {
            throw new ValidationException(
                "Cannot call writesDone() after streaming call is complete.");
        }
        if (!$this->sentWritesDone) {
            $this->call->writesDone();
            $this->sentWritesDone = true;
        }
    }

    /**
     * Read the next response from the server. Returns null if the streaming call completed
     * successfully. Throws an ApiException if the streaming call failed.
     *
     * @return mixed
     * @throws ValidationException
     * @throws ApiException
     */
    public function read()
    {
        if ($this->isComplete) {
            throw new ValidationException("Cannot call read() after streaming call is complete.");
        }
        $result = $this->call->read();
        if (is_null($result)) {
            $status = $this->call->getStatus();
            $this->isComplete = true;
            if (!($status->code == Grpc\STATUS_OK)) {
                throw new ApiException($status->details, $status->code);
            }
        }
        return $result;
    }

    /**
     * Call writesDone(), and read all responses from the server, until the streaming call is
     * completed. Throws an ApiException if the streaming call failed.
     *
     * @return \Generator|mixed[]
     * @throws ValidationException
     * @throws ApiException
     */
    public function closeAndReadAll()
    {
        $this->writesDone();
        $response = $this->read();
        while (!is_null($response)) {
            yield $response;
            $response = $this->read();
        }
    }

    /**
     * Return the underlying gRPC call object
     *
     * @return \Grpc\BidiStreamingCall
     */
    public function getBidiStreamingCall()
    {
        return $this->call;
    }
}
