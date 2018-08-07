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

class RelativeResourceTemplate
{
    /** @var Segment[] $segments */
    private $segments;

    /**
     * RelativeResourceTemplate constructor.
     *
     * @param string $path
     * @throws ValidationException
     */
    public function __construct($path)
    {
        if (empty($path)) {
            throw new ValidationException("Cannot construct RelativeResourceTemplate from empty string");
        }

        $segments = [];
        $index = 0;
        $doubleWildcardCount = 0;
        $segments[] = self::consumeSegment($path, $index);
        while ($index < strlen($path)) {
            self::consumeLiteral('/', $path, $index);
            $segment = self::consumeSegment($path, $index);
            $segments[] = $segment;
            if ($segment->getSegmentType() == Segment::DOUBLE_WILDCARD_SEGMENT) {
                $doubleWildcardCount++;
            }
        }
        if ($doubleWildcardCount > 1) {
            throw new ValidationException(
                "Cannot parse '$path': cannot contain more than one path wildcard"
            );
        }
        $this->segments = $segments;
    }

    /**
     * Given a path and an index, reads a Segment from the path and updates
     * the index.
     *
     * @param string $path
     * @param int $index
     * @return Segment
     * @throws ValidationException
     */
    private static function consumeSegment($path, &$index)
    {
        assert($index < strlen($path));
        if ($path[$index] === '/') {
            throw self::parseErrorUnexpected('/', $path, $index);
        } elseif ($path[$index] === '{') {
            // Validate that the { has a matching }
            $closingBraceIndex = strpos($path, '}', $index);
            if ($closingBraceIndex === false) {
                throw self::parseErrorUnexpected('{', $path, $index);
            }

            // Grab the segment string, update the index, and parse the segment
            $segmentStringWithBraces = substr($path, $index, $closingBraceIndex + 1 - $index);
            assert($segmentStringWithBraces[0] === '{');
            assert(substr($segmentStringWithBraces, -1) === '}');

            $index = $closingBraceIndex + 1;
            return Segment::parse($segmentStringWithBraces);
        } else {
            $nextSlash = strpos($path, '/', $index);
            if ($nextSlash === false) {
                $nextSlash = strlen($path);
            }
            $segmentString = substr($path, $index, $nextSlash - $index);
            $index = $nextSlash;
            return Segment::parse($segmentString);
        }
    }

    /**
     * @param string $literal
     * @param string $path
     * @param int $index
     * @return string
     * @throws ValidationException
     */
    private static function consumeLiteral($literal, $path, &$index)
    {
        if (strlen($path) < ($index + strlen($literal))) {
            throw self::parseError($literal, $path, $index);
        }
        $consumedLiteral = substr($path, $index, strlen($literal));
        if ($consumedLiteral !== $literal) {
            throw self::parseError($literal, $path, $index);
        }
        $index += strlen($literal);
        return $consumedLiteral;
    }

    private static function parseError($literal, $path, $index)
    {
        return new ValidationException(
            "Error parsing '$path' as index $index: " .
            "expected '$literal'"
        );
    }

    private static function parseErrorUnexpected($literal, $path, $index)
    {
        return new ValidationException(
            "Unexpected '$literal' in '$path' at index $index"
        );
    }

    /**
     * @return string A string representation of the resource template
     */
    public function __toString()
    {
        return implode("/", $this->segments);
    }

    /**
     * Renders a relative resource template using the provided bindings.
     *
     * @param array $bindings An array matching var names to binding strings.
     * @return string A rendered representation of this resource template.
     * @throws ValidationException If $bindings does not contain all required keys
     *         or if a sub-template can't be parsed.
     */
    public function render(array $bindings)
    {
        $boundSegments = $this->bind($bindings);
        return $this->renderSegments($boundSegments);
    }

    /**
     * @param array $bindings
     * @return array
     * @throws ValidationException
     */
    private function bind(array $bindings)
    {
        $positionalArgumentCounter = 0;
        $boundSegments = [];
        foreach ($this->segments as $segment) {
            if ($segment->isBound()) {
                $boundSegments[] = $segment;
                continue;
            }
            $key = self::getKey($segment, $positionalArgumentCounter);
            if (!array_key_exists($key, $bindings)) {
                throw new ValidationException(
                    "Rendering error - missing required binding '$key' for segment '$segment'."
                );
            }
            $boundSegment = $segment->bindTo($bindings[$key]);
            $boundSegments[] = $boundSegment;
        }
        return $boundSegments;
    }

    private static function getKey($segment, &$positionalArgumentCounter)
    {
        switch ($segment->getSegmentType()) {
            case Segment::WILDCARD_SEGMENT:
            case Segment::DOUBLE_WILDCARD_SEGMENT:
                $key = "\$$positionalArgumentCounter";
                $positionalArgumentCounter++;
                return $key;
            default:
                return $segment->getKey();
        }
    }

    /**
     * Renders a list of segments. May contain any type of segment, including bound and unbound
     * variables.
     *
     * @param array $segments An array of segments.
     * @return string A rendered representation of the segments.
     */
    private static function renderSegments(array $segments)
    {
        $renderedSegments = [];
        foreach ($segments as $segment) {
            $renderedSegments[] = $segment->render();
        }
        return implode("/", $renderedSegments);
    }

    /**
     * Matches a resource string.
     *
     * @param string $path A resource string.
     * @throws ValidationException if path can't be matched to the template.
     * @return array Array matching var names to binding values.
     */
    public function match($path)
    {
        $flattenedKeyedSegments = $this->buildFlattenedKeyedSegmentArray();
        $pathValues = explode('/', $path);

        assert(count($flattenedKeyedSegments) > 0);

        if (count($pathValues) < count($flattenedKeyedSegments)) {
            // Each segment in $flattenedKeyedSegments must consume at least one
            // segment in $pathSegments, so matching must fail.
            throw $this->matchException($path);
        }

        // Pop and compare segments from the end of the path and flattened segments. If
        // we encounter a double wildcard, stop and compare forwards from the
        // beginning of each array. This lets us determine how many segments to allocate
        // to the double wildcard.

        $bindings = [];
        $poppedTuple = array_pop($flattenedKeyedSegments);
        $poppedValue = array_pop($pathValues);
        $foundDoubleWildcard = false;

        do {
            list($poppedKey, $poppedSegment) = $poppedTuple;

            assert(!empty($poppedKey));
            assert($poppedSegment->getSegmentType() !== Segment::VARIABLE_SEGMENT);
            if ($poppedSegment->getSegmentType() === Segment::DOUBLE_WILDCARD_SEGMENT) {
                $foundDoubleWildcard = true;
                break;
            }

            if (!$poppedSegment->matchValue($poppedValue)) {
                throw $this->matchException($path);
            }

            // Found a match - add bindings if necessary
            if (isset($poppedKey)) {
                assert(!array_key_exists($poppedKey, $bindings));
                $bindings[$poppedKey] = $poppedValue;
            }
            $poppedTuple = array_pop($flattenedKeyedSegments);
            $poppedValue = array_pop($pathValues);
        } while (isset($poppedTuple));

        if (!$foundDoubleWildcard) {
            // Check that there are no unmatched values in path.
            if (count($pathValues) > 0) {
                throw $this->matchException($path);
            }
            return $bindings;
        }

        // We found a double wildcard - compare forwards until we exhaust all flattened
        // segments, and then construct the binding for the double wildcard from the
        // remaining values, including the value popped in the while loop above.
        $pathIndex = 0;
        foreach ($flattenedKeyedSegments as $segmentTuple) {
            list($key, $segment) = $segmentTuple;
            $value = $pathValues[$pathIndex];
            $pathIndex++;

            assert($segment->getSegmentType() !== Segment::VARIABLE_SEGMENT);
            assert($segment->getSegmentType() !== Segment::DOUBLE_WILDCARD_SEGMENT);

            if (!$segment->matchValue($value)) {
                throw $this->matchException($path);
            }

            // Found a match - add bindings if necessary
            if (isset($key)) {
                assert(!array_key_exists($key, $bindings));
                $bindings[$key] = $value;
            }
        }

        $doubleWildcardValues = array_slice($pathValues, $pathIndex);
        $doubleWildcardValues[] = $poppedValue;
        $bindings[$poppedKey] = implode('/', $doubleWildcardValues);

        return $bindings;
    }

    private function matchException($path)
    {
        return new ValidationException("Could not match path '$path' to template '$this'");
    }

    /**
     * In order to match elements from the path to segments, we need to flatten
     * our array of segments, because it may contain variable segments that
     * consist of other RelativeResourceTemplate. However, we need to determine the
     * correct keys for positional wildcards, and also need to keep track
     * of the variable name for any flattened segments in order to correctly
     * build the bindings array. Therefore, we flatten our segments into an array
     * of <ParentKey, Segment> tuples.
     * @throws ValidationException
     */
    private function buildFlattenedKeyedSegmentArray()
    {
        $flattenedKeyedSegments = [];
        $positionalArgumentCounter = 0;
        foreach ($this->segments as $segment) {
            switch ($segment->getSegmentType()) {
                case Segment::LITERAL_SEGMENT:
                    $flattenedKeyedSegments[] = [null, $segment];
                    break;
                case Segment::WILDCARD_SEGMENT:
                case Segment::DOUBLE_WILDCARD_SEGMENT:
                    $positionalKey = "\$$positionalArgumentCounter";
                    $positionalArgumentCounter++;
                    $flattenedKeyedSegments[] = [$positionalKey, $segment];
                    break;
                case Segment::VARIABLE_SEGMENT:
                    $key = $segment->getKey();
                    $template = $segment->getTemplate();
                    $innerFlattenedKeyedSegments = $template->buildFlattenedKeyedSegmentArray();
                    // Add flattened segment tuples to our list, replacing the key for each
                    // segment with the parent key
                    foreach ($innerFlattenedKeyedSegments as $segmentTuple) {
                        list($innerKey, $innerSegment) = $segmentTuple;
                        $flattenedKeyedSegments[] = [$key, $innerSegment];
                    }
                    break;
                default:
                    throw new ValidationException(
                        "Unexpected Segment type: {$segment->getSegmentType()}"
                    );
            }
        }
        return $flattenedKeyedSegments;
    }
}
