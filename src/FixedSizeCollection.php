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

class FixedSizeCollection implements IteratorAggregate
{
    private $collectionSize;
    private $pageList;

    public function __construct($initialPage, $collectionSize) {
        if ($collectionSize <= 0) {
            throw new InvalidArgumentException(
                "collectionSize must be > 0. collectionSize: $collectionSize");
        }
        if ($collectionSize < $initialPage->getPageElementCount()) {
            throw new InvalidArgumentException(
                "collectionSize must be greater than or equal to the number of " +
                "elements in initialPage. collectionSize: $collectionSize, " +
                "initialPage size: $initialPage->getPageElementCount()");
        }
        $this->collectionSize = $collectionSize;

        $this->pageList = FixedSizeCollection::createPageArray($initialPage, $collectionSize);
    }

    public function getCollectionSize() {
        return $this->collectionSize;
    }

    public function hasNextCollection() {
        return !is_null($this->getNextPageToken());
    }

    public function getNextPageToken() {
        return $this->getLastPage()->getNextPageToken();
    }

    public function getNextCollection() {
        $lastPage = $this->getLastPage();
        $nextPage = $lastPage->getNextPage($this->collectionSize);
        return new FixedSizeCollection($nextPage, $this->collectionSize);
    }

    public function iterateCollectionElements() {
        foreach ($this->pageList as $page) {
            foreach ($page as $element) {
                yield $element;
            }
        }
    }

    public function getIterator() {
        return $this->iterateCollectionElements();
    }

    public function iterateCollections() {
        $currentCollection = $this;
        yield $this;
        while ($currentCollection->hasNextCollection()) {
            $currentCollection = $currentCollection->getNextCollection();
            yield $currentCollection;
        }
    }

    private function getLastPage() {
        $pageList = $this->pageList;
        // Get last element in array...
        $lastPage = array_pop((array_slice($pageList, -1)));
        return $lastPage;
    }

    private static function createPageArray($initialPage, $collectionSize) {
        $pageList = [$initialPage];
        $currentPage = $initialPage;
        $itemCount = $currentPage->getPageElementCount();
        while ($itemCount < $collectionSize && $currentPage->hasNextPage()) {
            $remainingCount = $collectionSize - $itemCount;
            $currentPage = $currentPage->getNextPage($remainingCount);
            array_push($pageList, $currentPage);
            $itemCount += $currentPage->getPageElementCount();
        }
        return $pageList;
    }
}
