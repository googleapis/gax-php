<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/error_details.proto

namespace Google\Rpc;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Describes additional debugging info.
 * </pre>
 *
 * Protobuf type <code>google.rpc.DebugInfo</code>
 */
class DebugInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The stack trace entries indicating where the error occurred.
     * </pre>
     *
     * <code>repeated string stack_entries = 1;</code>
     */
    private $stack_entries;
    /**
     * <pre>
     * Additional debugging information provided by the server.
     * </pre>
     *
     * <code>string detail = 2;</code>
     */
    private $detail = '';

    public function __construct() {
        \GPBMetadata\Google\Rpc\ErrorDetails::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The stack trace entries indicating where the error occurred.
     * </pre>
     *
     * <code>repeated string stack_entries = 1;</code>
     */
    public function getStackEntries()
    {
        return $this->stack_entries;
    }

    /**
     * <pre>
     * The stack trace entries indicating where the error occurred.
     * </pre>
     *
     * <code>repeated string stack_entries = 1;</code>
     */
    public function setStackEntries(&$var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->stack_entries = $arr;
    }

    /**
     * <pre>
     * Additional debugging information provided by the server.
     * </pre>
     *
     * <code>string detail = 2;</code>
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * <pre>
     * Additional debugging information provided by the server.
     * </pre>
     *
     * <code>string detail = 2;</code>
     */
    public function setDetail($var)
    {
        GPBUtil::checkString($var, True);
        $this->detail = $var;
    }

}
