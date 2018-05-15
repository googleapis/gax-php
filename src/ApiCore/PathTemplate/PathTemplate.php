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

namespace Google\ApiCore\PathTemplate;

use Google\ApiCore\ValidationException;

/**
 * Represents a path template.
 *
 * Templates use the syntax of the API platform; see
 * https://github.com/googleapis/api-common-protos/blob/master/google/api/http.proto
 * for details. A template consists of a sequence of literals, wildcards, and variable bindings,
 * where each binding can have a sub-path. A string representation can be parsed into an
 * instance of PathTemplate, which can then be used to perform matching and instantiation.
 */
class PathTemplate
{
    /** @var ResourceTemplate */
    private $resourceTemplate;

    /** @var string */
    private $verb;

    /**
     * PathTemplate constructor.
     * @param string $path
     * @throws ValidationException
     */
    public function __construct($path)
    {
        if (!$path || $path[0] !== '/') {
            throw new ValidationException(
                "Could not construct PathTemplate from '$path': must begin with '/'"
            );
        }
        $verbSeparatorPos = $this->verbSeparatorPos($path);
        $this->resourceTemplate = new ResourceTemplate(substr($path, 1, $verbSeparatorPos - 1));
        $this->verb = substr($path, $verbSeparatorPos + 1);
    }

    /**
     * @return string A string representation of the path template
     */
    public function __toString()
    {
        return sprintf("/%s%s", $this->resourceTemplate, $this->renderVerb());
    }

    /**
     * Renders a path template using the provided bindings.
     *
     * @param array $bindings An array matching var names to binding strings.
     * @return string A rendered representation of this path template.
     * @throws ValidationException If $bindings does not contain all required keys
     *         or if a sub-template can't be parsed.
     */
    public function render($bindings)
    {
        return sprintf("/%s%s", $this->resourceTemplate->render($bindings), $this->renderVerb());
    }

    /**
     * Matches a fully qualified path template string.
     *
     * @param string $path A fully qualified path template string.
     * @throws ValidationException if path can't be matched to the template.
     * @return array Array matching var names to binding values.
     */
    public function match($path)
    {
        if (!$path || $path[0] !== '/') {
            throw $this->matchException($path);
        }
        $verbSeparatorPos = $this->verbSeparatorPos($path);
        if (substr($path, $verbSeparatorPos + 1) !== $this->verb) {
            throw $this->matchException($path);
        }
        return $this->resourceTemplate->match(substr($path, 1, $verbSeparatorPos - 1));
    }

    private function matchException($path)
    {
        return new ValidationException("Could not match path '$path' to template '$this'");
    }

    private function renderVerb()
    {
        return $this->verb ? ':' . $this->verb : '';
    }

    private function verbSeparatorPos($path)
    {
        $finalSeparatorPos = strrpos($path, '/');
        $verbSeparatorPos = strrpos($path, ':', $finalSeparatorPos);
        if ($verbSeparatorPos === false) {
            $verbSeparatorPos = strlen($path);
        }
        return $verbSeparatorPos;
    }
}
