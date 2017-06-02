<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/wrappers.proto

namespace Google\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Wrapper message for `int64`.
 * The JSON representation for `Int64Value` is JSON string.
 * </pre>
 *
 * Protobuf type <code>google.protobuf.Int64Value</code>
 */
class Int64Value extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The int64 value.
     * </pre>
     *
     * <code>int64 value = 1;</code>
     */
    private $value = 0;

    public function __construct() {
        \GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The int64 value.
     * </pre>
     *
     * <code>int64 value = 1;</code>
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * <pre>
     * The int64 value.
     * </pre>
     *
     * <code>int64 value = 1;</code>
     */
    public function setValue($var)
    {
        GPBUtil::checkInt64($var);
        $this->value = $var;
    }

}
