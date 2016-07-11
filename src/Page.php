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
namespace Google\GAX;

use IteratorAggregate;

class Page implements IteratorAggregate
{
    private $parameters;
    private $callable;
    private $pageStreamingDescriptor;

    private $pageToken;

    private $response;

    public function __construct($params, $callable, $pageStreamingDescriptor) {
        if (empty($params) || !is_object($params[0])) {
            throw new InvalidArgumentException('First argument must be a request object.');
        }
        $this->parameters = $params;
        $this->callable = $callable;
        $this->pageStreamingDescriptor = $pageStreamingDescriptor;

        $requestPageTokenField = $this->pageStreamingDescriptor->getRequestPageTokenField();
        $this->pageToken = $params[0]->$requestPageTokenField;

        // Make gRPC call eagerly
        $this->response = call_user_func_array($this->callable, $this->parameters);
    }

    public function hasNextPage() {
        return !is_null($this->getNextPageToken());
    }

    public function getNextPageToken() {
        $responsePageTokenField = $this->pageStreamingDescriptor->getResponsePageTokenField();
        return $this->getResponseObject()->$responsePageTokenField;
    }

    public function getNextPage($pageSize = null) {
        $newRequest = clone $this->getRequestObject();

        $requestPageTokenField = $this->pageStreamingDescriptor->getRequestPageTokenField();
        $newRequest->$requestPageTokenField = $this->getNextPageToken();

        if (isset($pageSize)) {
            $newRequest->setPageSize($pageSize);
        }

        $nextParameters = [$newRequest, $this->parameters[1], $this->parameters[2]];

        return new Page($nextParameters, $this->callable, $this->pageStreamingDescriptor);
    }

    public function getPageElementCount() {
        $resourceField = $this->pageStreamingDescriptor->getResourceField();
        return count($this->getResponseObject()->$resourceField);
    }

    public function iteratePageElements() {
        $resourceField = $this->pageStreamingDescriptor->getResourceField();
        foreach ($this->getResponseObject()->$resourceField as $element) {
            yield $element;
        }
    }

    public function getIterator() {
        return $this->iteratePageElements();
    }

    public function iteratePages() {
        $currentPage = $this;
        yield $this;
        while ($currentPage->hasNextPage()) {
            $currentPage = $currentPage->getNextPage();
            yield $currentPage;
        }
    }

    public function getRequestObject() {
        return $this->parameters[0];
    }

    public function getResponseObject() {
        return $this->response;
    }

    private static function createNewRequest($previousRequest, $nextPageToken, $pageSize = null) {
        $newRequest = clone $previousRequest;
        $requestPageTokenField = $this->pageStreamingDescriptor->getRequestPageTokenField();
        $newRequest->$requestPageTokenField = $nextPageToken;
        if (isset($pageSize)) {
            $newRequest->setPageSize($pageSize);
        }
        return $newRequest;
    }
}
