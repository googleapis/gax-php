<?php
/*
 * Copyright 2018, Google Inc.
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

use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\Middleware\AgentHeaderMiddleware;
use Google\ApiCore\Middleware\OperationsCallable;
use Google\ApiCore\Middleware\PagedCallable;
use Google\ApiCore\Middleware\CredentialsWrapperMiddleware;
use Google\ApiCore\Middleware\RetryMiddleware;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;
use Google\ApiCore\Transport\TransportInterface;
use Google\Auth\FetchAuthTokenInterface;
use Google\LongRunning\Operation;
use Google\Protobuf\Internal\Message;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Protected extension points that can be used to customize client
 * functionality.
 * 
 * @experimental
 * @access private
 */
trait GapicClientExtensionPointsTrait
{
    /**
     * Modify options passed to the client before calling setClientOptions.
     * 
     * @param array $options
     * @access private
     */
    protected function modifyClientOptions(array &$options)
    {
        // Do nothing - this method exists to allow option modification by partial veneers.
    }

    /**
     * Set custom options on the client.
     * 
     * @param array $options
     * @access private
     */
    protected function setCustomClientOptions($options)
    {
        // Do nothing - this method exists to allow setting custom options by partial veneers.
    }

    /**
     * Modify the unary callable.
     * 
     * @param callable $callable
     * @access private
     */
    protected function modifyUnaryCallable(&$callable)
    {
        // Do nothing - this method exists to allow callable modification by partial veneers.
    }

    /**
     * Modify the streaming callable.
     * 
     * @param callable $callable
     * @access private
     */
    protected function modifyStreamingCallable(&$callable)
    {
        // Do nothing - this method exists to allow callable modification by partial veneers.
    }
}
