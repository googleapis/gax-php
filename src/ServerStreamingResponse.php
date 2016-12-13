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

use IteratorAggregate;
use Grpc;

/**
 * ServerStreamingResponse is the response object from a gRPC server streaming API call.
 */
class ServerStreamingResponse implements IteratorAggregate
{
    private $call;

    /**
     * ServerStreamingResponse constructor.
     *
     * @param \Grpc\ServerStreamingCall $serverStreamingCall The gRPC server streaming call object
     */
    public function __construct($serverStreamingCall)
    {
        $this->call = $serverStreamingCall;
    }

    /**
     * A generator which yields the response objects from the server
     *
     * @return \Generator|mixed
     * @throws ApiException
     */
    public function getIterator()
    {
        foreach ($this->call->responses() as $response) {
            yield $response;
        }
        $status = $this->call->getStatus();
        if (!($status->code == Grpc\STATUS_OK)) {
            throw new ApiException($status->details, $status->code);
        }
    }

    /**
     * Return a single response object from the server, or null if there are no responses available
     *
     * @return mixed|null
     */
    public function read()
    {
        foreach ($this->getIterator() as $response) {
            return $response;
        }
        return null;
    }

    /**
     * Return the underlying gRPC call object
     *
     * @return Grpc\ServerStreamingCall
     */
    public function getServerStreamingCall()
    {
        return $this->call;
    }
}
