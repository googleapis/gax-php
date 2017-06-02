<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/duration.proto

namespace Google\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * A Duration represents a signed, fixed-length span of time represented
 * as a count of seconds and fractions of seconds at nanosecond
 * resolution. It is independent of any calendar and concepts like "day"
 * or "month". It is related to Timestamp in that the difference between
 * two Timestamp values is a Duration and it can be added or subtracted
 * from a Timestamp. Range is approximately +-10,000 years.
 * Example 1: Compute Duration from two Timestamps in pseudo code.
 *     Timestamp start = ...;
 *     Timestamp end = ...;
 *     Duration duration = ...;
 *     duration.seconds = end.seconds - start.seconds;
 *     duration.nanos = end.nanos - start.nanos;
 *     if (duration.seconds &lt; 0 &amp;&amp; duration.nanos &gt; 0) {
 *       duration.seconds += 1;
 *       duration.nanos -= 1000000000;
 *     } else if (durations.seconds &gt; 0 &amp;&amp; duration.nanos &lt; 0) {
 *       duration.seconds -= 1;
 *       duration.nanos += 1000000000;
 *     }
 * Example 2: Compute Timestamp from Timestamp + Duration in pseudo code.
 *     Timestamp start = ...;
 *     Duration duration = ...;
 *     Timestamp end = ...;
 *     end.seconds = start.seconds + duration.seconds;
 *     end.nanos = start.nanos + duration.nanos;
 *     if (end.nanos &lt; 0) {
 *       end.seconds -= 1;
 *       end.nanos += 1000000000;
 *     } else if (end.nanos &gt;= 1000000000) {
 *       end.seconds += 1;
 *       end.nanos -= 1000000000;
 *     }
 * Example 3: Compute Duration from datetime.timedelta in Python.
 *     td = datetime.timedelta(days=3, minutes=10)
 *     duration = Duration()
 *     duration.FromTimedelta(td)
 * </pre>
 *
 * Protobuf type <code>google.protobuf.Duration</code>
 */
class Duration extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * Signed seconds of the span of time. Must be from -315,576,000,000
     * to +315,576,000,000 inclusive.
     * </pre>
     *
     * <code>int64 seconds = 1;</code>
     */
    private $seconds = 0;
    /**
     * <pre>
     * Signed fractions of a second at nanosecond resolution of the span
     * of time. Durations less than one second are represented with a 0
     * `seconds` field and a positive or negative `nanos` field. For durations
     * of one second or more, a non-zero value for the `nanos` field must be
     * of the same sign as the `seconds` field. Must be from -999,999,999
     * to +999,999,999 inclusive.
     * </pre>
     *
     * <code>int32 nanos = 2;</code>
     */
    private $nanos = 0;

    public function __construct() {
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * Signed seconds of the span of time. Must be from -315,576,000,000
     * to +315,576,000,000 inclusive.
     * </pre>
     *
     * <code>int64 seconds = 1;</code>
     */
    public function getSeconds()
    {
        return $this->seconds;
    }

    /**
     * <pre>
     * Signed seconds of the span of time. Must be from -315,576,000,000
     * to +315,576,000,000 inclusive.
     * </pre>
     *
     * <code>int64 seconds = 1;</code>
     */
    public function setSeconds($var)
    {
        GPBUtil::checkInt64($var);
        $this->seconds = $var;
    }

    /**
     * <pre>
     * Signed fractions of a second at nanosecond resolution of the span
     * of time. Durations less than one second are represented with a 0
     * `seconds` field and a positive or negative `nanos` field. For durations
     * of one second or more, a non-zero value for the `nanos` field must be
     * of the same sign as the `seconds` field. Must be from -999,999,999
     * to +999,999,999 inclusive.
     * </pre>
     *
     * <code>int32 nanos = 2;</code>
     */
    public function getNanos()
    {
        return $this->nanos;
    }

    /**
     * <pre>
     * Signed fractions of a second at nanosecond resolution of the span
     * of time. Durations less than one second are represented with a 0
     * `seconds` field and a positive or negative `nanos` field. For durations
     * of one second or more, a non-zero value for the `nanos` field must be
     * of the same sign as the `seconds` field. Must be from -999,999,999
     * to +999,999,999 inclusive.
     * </pre>
     *
     * <code>int32 nanos = 2;</code>
     */
    public function setNanos($var)
    {
        GPBUtil::checkInt32($var);
        $this->nanos = $var;
    }

}

