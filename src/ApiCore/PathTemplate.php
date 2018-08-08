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

namespace Google\ApiCore;

use Google\ApiCore\ResourceTemplate\AbsoluteResourceTemplate;
use Google\ApiCore\ResourceTemplate\RelativeResourceTemplate;
use Google\ApiCore\ResourceTemplate\ResourceTemplateInterface;

/**
 * Represents a path template.
 *
 * Templates use the syntax of the API platform; see the protobuf of HttpRule for
 * details. A template consists of a sequence of literals, wildcards, and variable bindings,
 * where each binding can have a sub-path. A string representation can be parsed into an
 * instance of PathTemplate, which can then be used to perform matching and instantiation.
 */
class PathTemplate implements ResourceTemplateInterface
{
    private $resourceTemplate;

    public function __construct($path)
    {
        if (empty($path)) {
            throw new ValidationException("Cannot construct PathTemplate from empty string");
        }

        if ($path[0] === '/') {
            $this->resourceTemplate = new AbsoluteResourceTemplate($path);
        } else {
            $this->resourceTemplate = new RelativeResourceTemplate($path);
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->resourceTemplate->__toString();
    }

    /**
     * @inheritdoc
     */
    public function render(array $bindings)
    {
        return $this->resourceTemplate->render($bindings);
    }

    /**
     * @inheritdoc
     */
    public function matches($path)
    {
        return $this->resourceTemplate->matches($path);
    }

    /**
     * @inheritdoc
     */
    public function match($path)
    {
        return $this->resourceTemplate->match($path);
    }
}
