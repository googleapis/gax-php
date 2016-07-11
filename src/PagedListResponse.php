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

class PagedListResponse
{
    private $parameters;
    private $callable;
    private $pageStreamingDescriptor;

    public function __construct($params, $callable, $pageStreamingDescriptor) {
        if (empty($params) || !is_object($params[0])) {
            throw new InvalidArgumentException('First argument must be a request object.');
        }
        $this->parameters = $params;
        $this->callable = $callable;
        $this->pageStreamingDescriptor = $pageStreamingDescriptor;
    }

    public function iterateAllElements($pageSize = null) {
        foreach ($this->iteratePages($pageSize) as $page) {
            foreach ($page->iteratePageElements() as $element) {
                yield $element;
            }
        }
    }

    public function getPage($pageSize = null) {
        if (isset($pageSize)) {
            $this->parameters[0]->setPageSize($pageSize);
        }
        return new Page($this->parameters, $this->callable, $this->pageStreamingDescriptor);
    }

    public function iteratePages($pageSize = null) {
        return $this->getPage($pageSize)->iteratePages();
    }

    public function getFixedSizeCollection($collectionSize) {
        return new FixedSizeCollection($this->getPage($collectionSize), $collectionSize);
    }

    public function iterateFixedSizeCollections($collectionSize) {
        return $this->getFixedSizeCollection($collectionSize)->iterateCollections();
    }
}
