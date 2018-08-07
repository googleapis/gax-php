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
 * Represents a segment in a resource name.
 */
class Segment
{
    const LITERAL_SEGMENT = 0;
    const WILDCARD_SEGMENT = 1;
    const DOUBLE_WILDCARD_SEGMENT = 2;
    const VARIABLE_SEGMENT = 3;

    private $segmentType;
    private $key;
    private $value;
    private $template;
    private $valueBindings;

    public static function parse($segmentString)
    {
        if ($segmentString === '*') {
            return Segment::unboundWildcard();
        } elseif ($segmentString === '**') {
            return Segment::unboundDoubleWildcard();
        } elseif ($segmentString[0] === '{') {
            if (substr($segmentString, -1) !== '}') {
                throw new ValidationException(
                    "Expected '}' at end of segment $segmentString"
                );
            }

            // Validate there are no nested braces
            $segmentStringWithoutBraces = substr($segmentString, 1, strlen($segmentString) - 2);
            $nestedOpenBracket = strpos($segmentStringWithoutBraces, '{');
            if ($nestedOpenBracket !== false) {
                throw new ValidationException(
                    "Unexpected '{' parsing segment $segmentString at index $nestedOpenBracket"
                );
            }

            $equalsIndex = strpos($segmentStringWithoutBraces, '=');
            if ($equalsIndex === false) {
                $variableKey = $segmentStringWithoutBraces;
                $nestedResource = null;
            } else {
                $variableKey = substr($segmentStringWithoutBraces, 0, $equalsIndex);
                $nestedResourceString = substr($segmentStringWithoutBraces, $equalsIndex + 1);
                $nestedResource = new RelativeResourceTemplate($nestedResourceString);
            }

            return Segment::unboundVariable($variableKey, $nestedResource);
        } else {
            return Segment::literal($segmentString);
        }
    }

    private static function isValidLiteral($literal)
    {
        return preg_match("/^[0-9a-zA-Z\\.\\-~_]+$/", $literal);
    }

    public static function literal($value)
    {
        if (!self::isValidLiteral($value)) {
            throw new ValidationException(
                "Unexpected characters in literal segment $value"
            );
        }
        return new Segment(self::LITERAL_SEGMENT, null, $value);
    }

    public static function unboundWildcard()
    {
        return new Segment(self::WILDCARD_SEGMENT, null, null);
    }

    public static function unboundDoubleWildcard()
    {
        return new Segment(self::DOUBLE_WILDCARD_SEGMENT, null, null);
    }

    public static function boundWildcard($value)
    {
        return new Segment(self::WILDCARD_SEGMENT, null, $value);
    }

    public static function boundDoubleWildcard($value)
    {
        return new Segment(self::DOUBLE_WILDCARD_SEGMENT, null, $value);
    }

    public static function unboundVariable($key, RelativeResourceTemplate $template = null)
    {
        if (!self::isValidLiteral($key)) {
            throw new ValidationException(
                "Unexpected characters in variable name $key"
            );
        }
        if (is_null($template)) {
            $template = new RelativeResourceTemplate("*");
        }
        return new Segment(self::VARIABLE_SEGMENT, $key, null, $template);
    }

    /**
     * @param $key
     * @param RelativeResourceTemplate $template
     * @param $value
     * @return Segment
     * @throws ValidationException
     */
    public static function boundVariable($key, RelativeResourceTemplate $template, $value)
    {
        if (!self::isValidLiteral($key)) {
            throw new ValidationException(
                "Unexpected characters in variable name $key"
            );
        }
        $bindings = $template->match($value);
        return new Segment(self::VARIABLE_SEGMENT, $key, $value, $template, $bindings);
    }

    private function __construct($segmentType, $key, $value, $template = null, $valueBindings = null)
    {
        $this->segmentType = $segmentType;
        $this->key = $key;
        $this->value = $value;
        $this->template = $template;
        $this->valueBindings = $valueBindings;
    }

    /**
     * @return string A string representation of the segment.
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param mixed $value
     * @return Segment
     * @throws ValidationException
     */
    public function bindTo($value)
    {
        if ($this->isBound()) {
            throw new ValidationException(
                "Cannot bind segment '$this' as it is already bound."
            );
        }
        if (isset($this->template)) {
            $this->template->match($value);
        }
        return new Segment(
            $this->segmentType,
            $this->key,
            $value,
            $this->template,
            $this->valueBindings
        );
    }

    public function render()
    {
        if ($this->isBound()) {
            return $this->value;
        }
        switch ($this->segmentType) {
            case Segment::WILDCARD_SEGMENT:
                return "*";
            case Segment::DOUBLE_WILDCARD_SEGMENT:
                return "**";
            case Segment::VARIABLE_SEGMENT:
                $key = $this->key;
                $template = $this->template;
                return "{{$key}=$template}";
            default:
                throw new ValidationException(
                    "Unexpected Segment type: {$this->segmentType}"
                );
        }
    }

    /**
     * @param $value
     * @return bool
     * @throws ValidationException
     */
    public function matchValue($value)
    {
        switch ($this->segmentType) {
            case self::LITERAL_SEGMENT:
                return $this->value === $value;
            case self::WILDCARD_SEGMENT:
                return true;
            case self::DOUBLE_WILDCARD_SEGMENT:
                throw new ValidationException("Cannot call matchValue on DoubleWildcard segment");
            case self::VARIABLE_SEGMENT:
                throw new ValidationException("Cannot call matchValue on Variable segment");
            default:
                throw new ValidationException(
                    "Unexpected Segment type: {$this->segmentType}"
                );
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return RelativeResourceTemplate|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function isBound()
    {
        return isset($this->value);
    }

    public function getSegmentType()
    {
        return $this->segmentType;
    }
}
