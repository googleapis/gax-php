<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/quota.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Bind API methods to metrics. Binding a method to a metric causes that
 * metric's configured quota, billing, and monitoring behaviors to apply to the
 * method call.
 * Used by metric-based quotas only.
 * </pre>
 *
 * Protobuf type <code>google.api.MetricRule</code>
 */
class MetricRule extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * Selects the methods to which this rule applies.
     * Refer to [selector][google.api.DocumentationRule.selector] for syntax details.
     * </pre>
     *
     * <code>string selector = 1;</code>
     */
    private $selector = '';
    /**
     * <pre>
     * Metrics to update when the selected methods are called, and the associated
     * cost applied to each metric.
     * The key of the map is the metric name, and the values are the amount
     * increased for the metric against which the quota limits are defined.
     * The value must not be negative.
     * </pre>
     *
     * <code>map&lt;string, int64&gt; metric_costs = 2;</code>
     */
    private $metric_costs;

    public function __construct() {
        \GPBMetadata\Google\Api\Quota::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * Selects the methods to which this rule applies.
     * Refer to [selector][google.api.DocumentationRule.selector] for syntax details.
     * </pre>
     *
     * <code>string selector = 1;</code>
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * <pre>
     * Selects the methods to which this rule applies.
     * Refer to [selector][google.api.DocumentationRule.selector] for syntax details.
     * </pre>
     *
     * <code>string selector = 1;</code>
     */
    public function setSelector($var)
    {
        GPBUtil::checkString($var, True);
        $this->selector = $var;

        return $this;
    }

    /**
     * <pre>
     * Metrics to update when the selected methods are called, and the associated
     * cost applied to each metric.
     * The key of the map is the metric name, and the values are the amount
     * increased for the metric against which the quota limits are defined.
     * The value must not be negative.
     * </pre>
     *
     * <code>map&lt;string, int64&gt; metric_costs = 2;</code>
     */
    public function getMetricCosts()
    {
        return $this->metric_costs;
    }

    /**
     * <pre>
     * Metrics to update when the selected methods are called, and the associated
     * cost applied to each metric.
     * The key of the map is the metric name, and the values are the amount
     * increased for the metric against which the quota limits are defined.
     * The value must not be negative.
     * </pre>
     *
     * <code>map&lt;string, int64&gt; metric_costs = 2;</code>
     */
    public function setMetricCosts(&$var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::INT64);
        $this->metric_costs = $arr;

        return $this;
    }

}

