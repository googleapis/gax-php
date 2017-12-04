<?php
/*
 * Copyright 2017, Google LLC All rights reserved.
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
 *     * Neither the name of Google LLC nor the names of its
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

namespace Google\ApiCore\Tests\Unit\LongRunning;

use Google\ApiCore\ApiException;
use Google\ApiCore\LongRunning\OperationsClient;
use Google\ApiCore\Testing\GeneratedTest;
use Google\ApiCore\Tests\Mocks\MockOperationsClient;
use Google\ApiCore\Tests\Unit\TestTrait;
use Google\LongRunning\ListOperationsResponse;
use Google\LongRunning\Operation;
use Google\Protobuf\Any;
use Google\Protobuf\GPBEmpty;
use Grpc;
use stdClass;

/**
 * @group long_running
 * @group grpc
 */
class OperationsClientTest extends GeneratedTest
{
    use TestTrait;

    public function setUp()
    {
        $this->checkAndSkipGrpcTests();
    }

    /**
     * @return OperationsClient
     */
    private function createClient($options = [])
    {
        return new MockOperationsClient($options + [
            'serviceAddress' => 'unknown-service-address',
            'scopes' => ['unknown-service-scopes'],
            'transport' => 'grpc',
        ]);
    }

    /**
     * @test
     */
    public function getOperationTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        // Mock response
        $name2 = 'name2-1052831874';
        $done = true;
        $expectedResponse = new Operation();
        $expectedResponse->setName($name2);
        $expectedResponse->setDone($done);
        $client->addResponse($expectedResponse);

        // Mock request
        $name = 'name3373707';

        $response = $client->getOperation($name);
        $this->assertEquals($expectedResponse, $response);
        $actualRequests = $client->popReceivedCalls();
        $this->assertSame(1, count($actualRequests));
        $actualFuncCall = $actualRequests[0]->getFuncCall();
        $actualRequestObject = $actualRequests[0]->getRequestObject();
        $this->assertSame('/google.longrunning.Operations/GetOperation', $actualFuncCall);

        $val = $actualRequestObject->getName();
        $this->assertProtobufEquals($name, $val);

        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function getOperationExceptionTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        $status = new stdClass();
        $status->code = Grpc\STATUS_DATA_LOSS;
        $status->details = 'internal error';

        $expectedExceptionMessage = json_encode([
           'message' => 'internal error',
           'code' => Grpc\STATUS_DATA_LOSS,
           'status' => 'DATA_LOSS',
           'details' => [],
        ], JSON_PRETTY_PRINT);
        $client->addResponse(null, $status);

        // Mock request
        $name = 'name3373707';

        try {
            $client->getOperation($name);
            // If the $client method call did not throw, fail the test
            $this->fail('Expected an ApiException, but no exception was thrown.');
        } catch (ApiException $ex) {
            $this->assertEquals($status->code, $ex->getCode());
            $this->assertEquals($expectedExceptionMessage, $ex->getMessage());
        }

        // Call popReceivedCalls to ensure the stub is exhausted
        $client->popReceivedCalls();
        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function listOperationsTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        // Mock response
        $nextPageToken = '';
        $operationsElement = new Operation();
        $operations = [$operationsElement];
        $expectedResponse = new ListOperationsResponse();
        $expectedResponse->setNextPageToken($nextPageToken);
        $expectedResponse->setOperations($operations);
        $client->addResponse($expectedResponse);

        // Mock request
        $name = 'name3373707';
        $filter = 'filter-1274492040';

        $response = $client->listOperations($name, $filter);
        $this->assertEquals($expectedResponse, $response->getPage()->getResponseObject());
        $resources = iterator_to_array($response->iterateAllElements());
        $this->assertSame(1, count($resources));
        $this->assertEquals($expectedResponse->getOperations()[0], $resources[0]);

        $actualRequests = $client->popReceivedCalls();
        $this->assertSame(1, count($actualRequests));
        $actualFuncCall = $actualRequests[0]->getFuncCall();
        $actualRequestObject = $actualRequests[0]->getRequestObject();
        $this->assertSame('/google.longrunning.Operations/ListOperation', $actualFuncCall);

        $actualName = $actualRequestObject->getName();
        $this->assertProtobufEquals($name, $actualName);
        $actualFilter = $actualRequestObject->getFilter();
        $this->assertProtobufEquals($filter, $actualFilter);
        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function listOperationsExceptionTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        $status = new stdClass();
        $status->code = Grpc\STATUS_DATA_LOSS;
        $status->details = 'internal error';

        $expectedExceptionMessage = json_encode([
           'message' => 'internal error',
           'code' => Grpc\STATUS_DATA_LOSS,
           'status' => 'DATA_LOSS',
           'details' => [],
        ], JSON_PRETTY_PRINT);
        $client->addResponse(null, $status);

        // Mock request
        $name = 'name3373707';
        $filter = 'filter-1274492040';

        try {
            $client->listOperations($name, $filter);
            // If the $client method call did not throw, fail the test
            $this->fail('Expected an ApiException, but no exception was thrown.');
        } catch (ApiException $ex) {
            $this->assertEquals($status->code, $ex->getCode());
            $this->assertEquals($expectedExceptionMessage, $ex->getMessage());
        }

        // Call popReceivedCalls to ensure the stub is exhausted
        $client->popReceivedCalls();
        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function cancelOperationTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        // Mock response
        $expectedResponse = new GPBEmpty();
        $client->addResponse($expectedResponse);

        // Mock request
        $name = 'name3373707';

        $client->cancelOperation($name);
        $actualRequests = $client->popReceivedCalls();
        $this->assertSame(1, count($actualRequests));
        $actualFuncCall = $actualRequests[0]->getFuncCall();
        $actualRequestObject = $actualRequests[0]->getRequestObject();
        $this->assertSame('/google.longrunning.Operations/CancelOperation', $actualFuncCall);

        $actualName = $actualRequestObject->getName();
        $this->assertProtobufEquals($name, $actualName);

        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function cancelOperationExceptionTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        $status = new stdClass();
        $status->code = Grpc\STATUS_DATA_LOSS;
        $status->details = 'internal error';

        $expectedExceptionMessage = json_encode([
           'message' => 'internal error',
           'code' => Grpc\STATUS_DATA_LOSS,
           'status' => 'DATA_LOSS',
           'details' => [],
        ], JSON_PRETTY_PRINT);
        $client->addResponse(null, $status);

        // Mock request
        $name = 'name3373707';

        try {
            $client->cancelOperation($name);
            // If the $client method call did not throw, fail the test
            $this->fail('Expected an ApiException, but no exception was thrown.');
        } catch (ApiException $ex) {
            $this->assertEquals($status->code, $ex->getCode());
            $this->assertEquals($expectedExceptionMessage, $ex->getMessage());
        }

        // Call popReceivedCalls to ensure the stub is exhausted
        $client->popReceivedCalls();
        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function deleteOperationTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        // Mock response
        $expectedResponse = new GPBEmpty();
        $client->addResponse($expectedResponse);

        // Mock request
        $name = 'name3373707';

        $client->deleteOperation($name);
        $actualRequests = $client->popReceivedCalls();
        $this->assertSame(1, count($actualRequests));
        $actualFuncCall = $actualRequests[0]->getFuncCall();
        $actualRequestObject = $actualRequests[0]->getRequestObject();
        $this->assertSame('/google.longrunning.Operations/DeleteOperation', $actualFuncCall);

        $actualName = $actualRequestObject->getName();
        $this->assertProtobufEquals($name, $actualName);

        $this->assertTrue($client->isExhausted());
    }

    /**
     * @test
     */
    public function deleteOperationExceptionTest()
    {
        $client = $this->createClient();

        $this->assertTrue($client->isExhausted());

        $status = new stdClass();
        $status->code = Grpc\STATUS_DATA_LOSS;
        $status->details = 'internal error';

        $expectedExceptionMessage = json_encode([
           'message' => 'internal error',
           'code' => Grpc\STATUS_DATA_LOSS,
           'status' => 'DATA_LOSS',
           'details' => [],
        ], JSON_PRETTY_PRINT);
        $client->addResponse(null, $status);

        // Mock request
        $name = 'name3373707';

        try {
            $client->deleteOperation($name);
            // If the $client method call did not throw, fail the test
            $this->fail('Expected an ApiException, but no exception was thrown.');
        } catch (ApiException $ex) {
            $this->assertEquals($status->code, $ex->getCode());
            $this->assertEquals($expectedExceptionMessage, $ex->getMessage());
        }

        // Call popReceivedCalls to ensure the stub is exhausted
        $client->popReceivedCalls();
        $this->assertTrue($client->isExhausted());
    }
}
