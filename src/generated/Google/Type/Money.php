<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/type/money.proto

namespace Google\Type;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Represents an amount of money with its currency type.
 * </pre>
 *
 * Protobuf type <code>google.type.Money</code>
 */
class Money extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The 3-letter currency code defined in ISO 4217.
     * </pre>
     *
     * <code>string currency_code = 1;</code>
     */
    private $currency_code = '';
    /**
     * <pre>
     * The whole units of the amount.
     * For example if `currencyCode` is `"USD"`, then 1 unit is one US dollar.
     * </pre>
     *
     * <code>int64 units = 2;</code>
     */
    private $units = 0;
    /**
     * <pre>
     * Number of nano (10^-9) units of the amount.
     * The value must be between -999,999,999 and +999,999,999 inclusive.
     * If `units` is positive, `nanos` must be positive or zero.
     * If `units` is zero, `nanos` can be positive, zero, or negative.
     * If `units` is negative, `nanos` must be negative or zero.
     * For example $-1.75 is represented as `units`=-1 and `nanos`=-750,000,000.
     * </pre>
     *
     * <code>int32 nanos = 3;</code>
     */
    private $nanos = 0;

    public function __construct() {
        \GPBMetadata\Google\Type\Money::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The 3-letter currency code defined in ISO 4217.
     * </pre>
     *
     * <code>string currency_code = 1;</code>
     */
    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    /**
     * <pre>
     * The 3-letter currency code defined in ISO 4217.
     * </pre>
     *
     * <code>string currency_code = 1;</code>
     */
    public function setCurrencyCode($var)
    {
        GPBUtil::checkString($var, True);
        $this->currency_code = $var;
    }

    /**
     * <pre>
     * The whole units of the amount.
     * For example if `currencyCode` is `"USD"`, then 1 unit is one US dollar.
     * </pre>
     *
     * <code>int64 units = 2;</code>
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * <pre>
     * The whole units of the amount.
     * For example if `currencyCode` is `"USD"`, then 1 unit is one US dollar.
     * </pre>
     *
     * <code>int64 units = 2;</code>
     */
    public function setUnits($var)
    {
        GPBUtil::checkInt64($var);
        $this->units = $var;
    }

    /**
     * <pre>
     * Number of nano (10^-9) units of the amount.
     * The value must be between -999,999,999 and +999,999,999 inclusive.
     * If `units` is positive, `nanos` must be positive or zero.
     * If `units` is zero, `nanos` can be positive, zero, or negative.
     * If `units` is negative, `nanos` must be negative or zero.
     * For example $-1.75 is represented as `units`=-1 and `nanos`=-750,000,000.
     * </pre>
     *
     * <code>int32 nanos = 3;</code>
     */
    public function getNanos()
    {
        return $this->nanos;
    }

    /**
     * <pre>
     * Number of nano (10^-9) units of the amount.
     * The value must be between -999,999,999 and +999,999,999 inclusive.
     * If `units` is positive, `nanos` must be positive or zero.
     * If `units` is zero, `nanos` can be positive, zero, or negative.
     * If `units` is negative, `nanos` must be negative or zero.
     * For example $-1.75 is represented as `units`=-1 and `nanos`=-750,000,000.
     * </pre>
     *
     * <code>int32 nanos = 3;</code>
     */
    public function setNanos($var)
    {
        GPBUtil::checkInt32($var);
        $this->nanos = $var;
    }

}

