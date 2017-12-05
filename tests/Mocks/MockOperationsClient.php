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
 * This file was automatically generated - do not edit!
 */

namespace Google\ApiCore\UnitTests\Mocks;

use Google\ApiCore\ApiException;
use Google\ApiCore\ApiTransportTrait;
use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\PageStreamingDescriptor;
use Google\ApiCore\PagedListResponse;
use Google\LongRunning\CancelOperationRequest;
use Google\LongRunning\GetOperationRequest;
use Google\LongRunning\ListOperationsRequest;
use Google\LongRunning\DeleteOperationRequest;
use Google\Rpc\Code;
use GuzzleHttp\Promise\Promise;

class MockOperationsClient
{
    use ApiTransportTrait;
    use MockStubTrait;

    /**
     * Lists operations that match the specified filter in the request. If the
     * server doesn't support this method, it returns `UNIMPLEMENTED`.
     *
     * NOTE: the `name` binding below allows API services to override the binding
     * to use different resource name schemes, such as `users/&#42;/operations`.
     * @param \Google\LongRunning\ListOperationsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function listOperations($name, $filter)
    {
        $request = new ListOperationsRequest();
        $request->setName($name);
        $request->setFilter($filter);
        $call = new Call('google.longrunning.Operations/ListOperation', '\Google\LongRunning\ListOperationsResponse', $request);
        $response = $this->startCall($call, new CallSettings);

        return new PagedListResponse(
            $call,
            new CallSettings,
            function () use ($response) {
                return $response;
            },
            new PageStreamingDescriptor([
                'requestPageTokenGetMethod' => 'getPageToken',
                'requestPageTokenSetMethod' => 'setPageToken',
                'requestPageSizeGetMethod' => 'getPageSize',
                'requestPageSizeSetMethod' => 'setPageSize',
                'responsePageTokenGetMethod' => 'getNextPageToken',
                'resourcesGetMethod' => 'getOperations',
            ])
        );
    }

    /**
     * Gets the latest state of a long-running operation.  Clients can use this
     * method to poll the operation result at intervals as recommended by the API
     * service.
     * @param \Google\LongRunning\GetOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function getOperation($name, $metadata = [], $options = [])
    {
        $request = new GetOperationRequest();
        $request->setName($name);
        $call = new Call('google.longrunning.Operations/GetOperation', '\Google\LongRunning\Operation', $request);
        return $this->startCall($call, new CallSettings)->wait();
    }

    /**
     * Deletes a long-running operation. This method indicates that the client is
     * no longer interested in the operation result. It does not cancel the
     * operation. If the server doesn't support this method, it returns
     * `google.rpc.Code.UNIMPLEMENTED`.
     * @param \Google\LongRunning\DeleteOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function deleteOperation($name, $metadata = [], $options = [])
    {
        $request = new DeleteOperationRequest();
        $request->setName($name);
        $call = new Call('google.longrunning.Operations/DeleteOperation', null, $request);
        return $this->startCall($call, new CallSettings)->wait();
    }

    /**
     * Starts asynchronous cancellation on a long-running operation.  The server
     * makes a best effort to cancel the operation, but success is not
     * guaranteed.  If the server doesn't support this method, it returns
     * `google.rpc.Code.UNIMPLEMENTED`.  Clients can use
     * [Operations.GetOperation][google.longrunning.Operations.GetOperation] or
     * other methods to check whether the cancellation succeeded or whether the
     * operation completed despite cancellation. On successful cancellation,
     * the operation is not deleted; instead, it becomes an operation with
     * an [Operation.error][google.longrunning.Operation.error] value with a [google.rpc.Status.code][google.rpc.Status.code] of 1,
     * corresponding to `Code.CANCELLED`.
     * @param \Google\LongRunning\CancelOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function cancelOperation($name, $metadata = [], $options = [])
    {
        $request = new CancelOperationRequest;
        $request->setName($name);
        $call = new Call('google.longrunning.Operations/CancelOperation', null, $request);
        return $this->startCall($call, new CallSettings)->wait();
    }

    private function getCallable(CallSettings $settings)
    {
        $callable = function (Call $call, CallSettings $settings) {
            $internalCall = $this->_simpleRequest(
                '/' . $call->getMethod(),
                $call->getMessage(),
                $call->getDecodeType()
                    ? [$call->getDecodeType(), 'decode']
                    : null,
                $settings->getUserHeaders() ?: [],
                $this->getOptions($settings)
            );

            $promise = new Promise(
                function () use ($internalCall, &$promise) {
                    list($response, $status) = $internalCall->wait();

                    if ($status->code == Code::OK) {
                        $promise->resolve($response);
                    } else {
                        throw ApiException::createFromStdClass($status);
                    }
                },
                [$internalCall, 'cancel']
            );

            return $promise;
        };

        return $callable;
    }

    private function getOptions(CallSettings $settings)
    {
        $retrySettings = $settings->getRetrySettings();
        $options = $settings->getGrpcOptions() ?: [];

        if ($retrySettings && $retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
            $options['timeout'] = $retrySettings->getNoRetriesRpcTimeoutMillis() * 1000;
        }

        return $options;
    }
}
