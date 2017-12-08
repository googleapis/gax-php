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
namespace Google\ApiCore\UnitTests;

use Google\ApiCore\Call;
use Google\ApiCore\CallSettings;
use Google\ApiCore\Page;
use Google\ApiCore\FixedSizeCollection;
use Google\ApiCore\PageStreamingDescriptor;
use Google\ApiCore\UnitTests\Mocks\MockStatus;
use PHPUnit\Framework\TestCase;
use Grpc;

class FixedSizeCollectionTest extends TestCase
{
    use TestTrait;

    private function createPage($responseSequence)
    {
        $mockRequest = $this->createMockRequest('token', 3);

        $pageStreamingDescriptor = PageStreamingDescriptor::createFromFields([
            'requestPageTokenField' => 'pageToken',
            'requestPageSizeField' => 'pageSize',
            'responsePageTokenField' => 'nextPageToken',
            'resourceField' => 'resourcesList'
        ]);

        $internalCall = $this->createCallWithResponseSequence($responseSequence);

        $callable = function () use ($internalCall) {
            list($response, $status) = call_user_func_array(
                array($internalCall, 'takeAction'),
                func_get_args()
            );
            return $promise = new \GuzzleHttp\Promise\Promise(function () use (&$promise, $response) {
                $promise->resolve($response);
            });
        };

        $call = new Call('method', [], $mockRequest);

        return new Page($call, new CallSettings, $callable, $pageStreamingDescriptor);
    }

    public function testFixedCollectionMethods()
    {
        $responseA = $this->createMockResponse(
            'nextPageToken1',
            ['resource1', 'resource2']
        );
        $responseB = $this->createMockResponse(
            'nextPageToken2',
            ['resource3', 'resource4', 'resource5']
        );
        $responseC = $this->createMockResponse(
            'nextPageToken3',
            ['resource6', 'resource7']
        );
        $responseD = $this->createMockResponse(
            '',
            ['resource8', 'resource9']
        );
        $page = $this->createPage([
            [$responseA, new MockStatus(Grpc\STATUS_OK, '')],
            [$responseB, new MockStatus(Grpc\STATUS_OK, '')],
            [$responseC, new MockStatus(Grpc\STATUS_OK, '')],
            [$responseD, new MockStatus(Grpc\STATUS_OK, '')],
        ]);

        $fixedSizeCollection = new FixedSizeCollection($page, 5);

        $this->assertEquals($fixedSizeCollection->getCollectionSize(), 5);
        $this->assertEquals($fixedSizeCollection->hasNextCollection(), true);
        $this->assertEquals($fixedSizeCollection->getNextPageToken(), 'nextPageToken2');
        $results = iterator_to_array($fixedSizeCollection);
        $this->assertEquals(
            $results,
            ['resource1', 'resource2', 'resource3', 'resource4', 'resource5']
        );

        $nextCollection = $fixedSizeCollection->getNextCollection();

        $this->assertEquals($nextCollection->getCollectionSize(), 4);
        $this->assertEquals($nextCollection->hasNextCollection(), false);
        $this->assertEquals($nextCollection->getNextPageToken(), '');
        $results = iterator_to_array($nextCollection);
        $this->assertEquals(
            $results,
            ['resource6', 'resource7', 'resource8', 'resource9']
        );
    }
}
