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
namespace Google\ApiCore\Tests\Unit;

<<<<<<< HEAD:tests/ApiCore/Tests/Unit/PagedListResponseTest.php
use Google\ApiCore\PagedListResponse;
use Google\ApiCore\PageStreamingDescriptor;
use Google\ApiCore\Tests\Unit\Mocks\MockStub;
use Google\ApiCore\Tests\Unit\Mocks\MockPageStreamingRequest;
use Google\ApiCore\Tests\Unit\Mocks\MockPageStreamingResponse;
=======
use Google\GAX\PagedListResponse;
use Google\GAX\PageStreamingDescriptor;
use Google\GAX\UnitTests\Mocks\MockRequest;
use Google\GAX\UnitTests\Mocks\MockResponse;
>>>>>>> Refactorings for Multi Transport:tests/PagedListResponseTest.php
use PHPUnit\Framework\TestCase;

class PagedListResponseTest extends TestCase
{
    use TestTrait;

    public function testNextPageToken()
    {
        $mockRequest = $this->createMockRequest('mockToken');

        $mockResponse = $this->createMockResponse('nextPageToken1', ['resource1']);

        $descriptor = PageStreamingDescriptor::createFromFields([
            'requestPageTokenField' => 'pageToken',
            'responsePageTokenField' => 'nextPageToken',
            'resourceField' => 'resourcesList'
        ]);

        $mockApiCall = function () use ($mockResponse) {
            return $mockResponse;
        };

        $pageAccessor = new PagedListResponse([$mockRequest, [], []], $mockApiCall, $descriptor);
        $page = $pageAccessor->getPage();
        $this->assertEquals($page->getNextPageToken(), 'nextPageToken1');
        $this->assertEquals(iterator_to_array($page->getIterator()), ['resource1']);
    }
}
