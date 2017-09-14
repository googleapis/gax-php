<?php
/*
 * Copyright 2017, Google Inc. All rights reserved.
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

/*
 * GENERATED CODE WARNING
 * This file was generated from the file
 * https://github.com/google/googleapis/blob/master/google/longrunning/operations.proto
 * and updates to that file get reflected here through a refresh process.
 *
 * EXPERIMENTAL: this client library class has not yet been declared beta. This class may change
 * more frequently than those which have been declared beta or 1.0, including changes which break
 * backwards compatibility.
 *
 * @experimental
 */

namespace Google\GAX\LongRunning;

use Google\GAX\AgentHeaderDescriptor;
use Google\GAX\ApiCallable;
use Google\GAX\CallSettings;
use Google\GAX\GrpcConstants;
use Google\GAX\GrpcCredentialsHelper;
use Google\GAX\PageStreamingDescriptor;
use Google\GAX\ValidationException;
use Google\Longrunning\CancelOperationRequest;
use Google\Longrunning\DeleteOperationRequest;
use Google\Longrunning\GetOperationRequest;
use Google\Longrunning\ListOperationsRequest;
use Google\Longrunning\ListOperationsResponse;
use Google\Longrunning\OperationsGrpcClient;


interface OperationsTransportInterface
{
    /**
     * @param GetOperationRequest $request
     * @param array  $optionalArgs {
     *                             Optional.
     *     @type integer $timeoutMillis
     *     @type array $headers
     *     @type \Google\Auth\CredentialsLoader $credentialsLoader
     * }
     *
     * @return \Google\GAX\UnaryCall with wait() returning \Google\Longrunning\Operation
     * @throws \Google\GAX\ApiException if the remote call fails
     */
    public function GetOperation(GetOperationRequest $request, $optionalArgs = []);

    /**
     * @param ListOperationsRequest $request
     * @param array  $optionalArgs {
     *                             Optional.
     *     @type integer $timeoutMillis
     *     @type array $headers
     *     @type \Google\Auth\CredentialsLoader $credentialsLoader
     * }
     *
     * @return \Google\GAX\UnaryCall with wait() returning ListOperationsResponse
     *
     * @throws \Google\GAX\ApiException if the remote call fails
     */
    public function ListOperations(ListOperationsRequest $request, $optionalArgs = []);

    /**
     * @param CancelOperationRequest $request
     * @param array  $optionalArgs {
     *                             Optional.
     *     @type integer $timeoutMillis
     *     @type array $headers
     *     @type \Google\Auth\CredentialsLoader $credentialsLoader

     * }
     *
     * @return \Google\GAX\UnaryCall with wait() returning CancelOperationsResponse
     * @throws \Google\GAX\ApiException if the remote call fails
     */
    public function cancelOperation(CancelOperationRequest $request, $optionalArgs = []);

    /**
     * @param DeleteOperationRequest $request
     * @param array  $optionalArgs {
     *                             Optional.
     *     @type integer $timeoutMillis
     *     @type array $headers
     *     @type \Google\Auth\CredentialsLoader $credentialsLoader
     * }
     *
     * @return \Google\GAX\UnaryCall with wait() returning DeleteOperationsResponse
     * @throws \Google\GAX\ApiException if the remote call fails
     */
    public function deleteOperation(DeleteOperationRequest $request, $optionalArgs = []);

}
