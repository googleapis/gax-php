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

/**
 * ClientStreamingResponse is the response object from a gRPC client streaming API call.
 */
class BidiStreamingResponse
{
    private $call;

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
     */
    public function write($data)
    {
        $this->call->write($data);
    }

    /**
     * Write all data in $dataArray, and return an iterator of response objects.
     *
     * @param mixed[] $dataArray An iterator of data objects to write to the server
     * @return \Generator|mixed[]
     */
    public function writeAllAndReadAll($dataArray = [])
    {
        foreach ($dataArray as $data) {
            $this->write($data);
        }
        $this->call->writesDone();
        return $this->readAll();
    }

    /**
     * Read the next response from the server. Returns null if no response is available.
     *
     * @return mixed
     */
    public function read()
    {
        return $this->call->read();
    }

    /**
     * Read all available responses from the server, and check the status once all response are
     * exhausted.
     *
     * @return \Generator|mixed[]
     * @throws ApiException
     */
    public function readAll()
    {
        $response = $this->read();
        while (!is_null($response)) {
            yield $response;
            $response = $this->read();
        }
        $status = $this->call->getStatus();
        if (!($status->code == Grpc\STATUS_OK)) {
            throw new ApiException($status->details, $status->code);
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
