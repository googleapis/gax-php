<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/wrappers.proto

namespace Google\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Wrapper message for `string`.
 * The JSON representation for `StringValue` is JSON string.
 * </pre>
 *
 * Protobuf type <code>google.protobuf.StringValue</code>
 */
class StringValue extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The string value.
     * </pre>
     *
     * <code>string value = 1;</code>
     */
    private $value = '';

    public function __construct() {
        \GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The string value.
     * </pre>
     *
     * <code>string value = 1;</code>
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * <pre>
     * The string value.
     * </pre>
     *
     * <code>string value = 1;</code>
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->value = $var;
    }

}
