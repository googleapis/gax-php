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
    private static $literalSegment = 0;
    private static $wildcardSegment = 1;
    private static $doubleWildcardSegment = 2;
    private static $variableSegment = 3;

    private $segmentType;
    private $key;
    private $value;
    private $template;
    private $valueBindings;

    public static function literal($value)
    {
        return new Segment(self::$literalSegment, null, $value);
    }

    public static function unboundWildcard($position)
    {
        $key = self::wildcardKeyFromPosition($position);
        return new Segment(self::$wildcardSegment, $key, null);
    }

    public static function unboundDoubleWildcard($position)
    {
        $key = self::wildcardKeyFromPosition($position);
        return new Segment(self::$doubleWildcardSegment, $key, null);
    }

    public static function boundWildcard($position, $value)
    {
        $key = self::wildcardKeyFromPosition($position);
        return new Segment(self::$wildcardSegment, $key, $value);
    }

    public static function boundDoubleWildcard($position, $value)
    {
        $key = self::wildcardKeyFromPosition($position);
        return new Segment(self::$doubleWildcardSegment, $key, $value);
    }

    public static function unboundVariable($key, ResourceTemplate $template = null)
    {
        if (is_null($template)) {
            $template = new ResourceTemplate("*");
        }
        return new Segment(self::$variableSegment, $key, null, $template);
    }

    /**
     * @param $key
     * @param ResourceTemplate $template
     * @param $value
     * @return Segment
     * @throws ValidationException
     */
    public static function boundVariable($key, ResourceTemplate $template, $value)
    {
        $bindings = $template->match($value);
        return new Segment(self::$variableSegment, $key, $value, $template, $bindings);
    }

    private static function wildcardKeyFromPosition($position)
    {
        return "\$$position";
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
     * @param array $bindings
     * @return Segment
     * @throws ValidationException
     */
    public function bind(array $bindings = [])
    {
        if ($this->isBound()) {
            return $this;
        }
        if (!array_key_exists($this->key, $bindings)) {
            throw new ValidationException(
                'Rendering error - missing required binding ' . $this->key
            );
        }
        $boundValue = $bindings[$this->key];

        switch ($this->segmentType) {
            case Segment::$wildcardSegment:
                return Segment::boundWildcard($this->key, $boundValue);
            case Segment::$doubleWildcardSegment:
                return Segment::boundDoubleWildcard($this->key, $boundValue);
            case Segment::$variableSegment:
                return Segment::boundVariable($this->key, $this->template, $boundValue);
        }
    }

    public function render()
    {
        if ($this->isBound()) {
            return $this->value;
        }
        switch ($this->segmentType) {
            case Segment::$wildcardSegment:
                return "*";
            case Segment::$doubleWildcardSegment:
                return "**";
            case Segment::$variableSegment:
                $key = $this->key;
                $template = $this->template;
                return "{{$key}=$template}";
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
            case self::$literalSegment:
                return $this->value === $value;
            case self::$wildcardSegment:
                return true;
            case self::$doubleWildcardSegment:
                throw new ValidationException("Cannot call matchValue on DoubleWildcard segment");
            case self::$variableSegment:
                throw new ValidationException("Cannot call matchValue on Variable segment");
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return ResourceTemplate|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function isBound()
    {
        return !is_null($this->value);
    }

    public function isVariable()
    {
        return $this->segmentType === self::$variableSegment;
    }

    public function isDoubleWildcard()
    {
        return $this->segmentType === self::$doubleWildcardSegment;
    }
}
