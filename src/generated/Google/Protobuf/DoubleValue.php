<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/protobuf/wrappers.proto

namespace Google\Protobuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Wrapper message for `double`.
 * The JSON representation for `DoubleValue` is JSON number.
 * </pre>
 *
 * Protobuf type <code>google.protobuf.DoubleValue</code>
 */
class DoubleValue extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The double value.
     * </pre>
     *
     * <code>double value = 1;</code>
     */
    private $value = 0.0;

    public function __construct() {
        \GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The double value.
     * </pre>
     *
     * <code>double value = 1;</code>
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * <pre>
     * The double value.
     * </pre>
     *
     * <code>double value = 1;</code>
     */
    public function setValue($var)
    {
        GPBUtil::checkDouble($var);
        $this->value = $var;
    }

}
