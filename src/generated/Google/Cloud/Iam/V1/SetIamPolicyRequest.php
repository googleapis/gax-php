<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/iam/v1/iam_policy.proto

namespace Google\Cloud\Iam\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Request message for `SetIamPolicy` method.
 * </pre>
 *
 * Protobuf type <code>google.iam.v1.SetIamPolicyRequest</code>
 */
class SetIamPolicyRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * REQUIRED: The resource for which the policy is being specified.
     * `resource` is usually specified as a path. For example, a Project
     * resource is specified as `projects/{project}`.
     * </pre>
     *
     * <code>string resource = 1;</code>
     */
    private $resource = '';
    /**
     * <pre>
     * REQUIRED: The complete policy to be applied to the `resource`. The size of
     * the policy is limited to a few 10s of KB. An empty policy is a
     * valid policy but certain Cloud Platform services (such as Projects)
     * might reject them.
     * </pre>
     *
     * <code>.google.iam.v1.Policy policy = 2;</code>
     */
    private $policy = null;

    public function __construct() {
        \GPBMetadata\Google\Iam\V1\IamPolicy::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * REQUIRED: The resource for which the policy is being specified.
     * `resource` is usually specified as a path. For example, a Project
     * resource is specified as `projects/{project}`.
     * </pre>
     *
     * <code>string resource = 1;</code>
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * <pre>
     * REQUIRED: The resource for which the policy is being specified.
     * `resource` is usually specified as a path. For example, a Project
     * resource is specified as `projects/{project}`.
     * </pre>
     *
     * <code>string resource = 1;</code>
     */
    public function setResource($var)
    {
        GPBUtil::checkString($var, True);
        $this->resource = $var;

        return $this;
    }

    /**
     * <pre>
     * REQUIRED: The complete policy to be applied to the `resource`. The size of
     * the policy is limited to a few 10s of KB. An empty policy is a
     * valid policy but certain Cloud Platform services (such as Projects)
     * might reject them.
     * </pre>
     *
     * <code>.google.iam.v1.Policy policy = 2;</code>
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * <pre>
     * REQUIRED: The complete policy to be applied to the `resource`. The size of
     * the policy is limited to a few 10s of KB. An empty policy is a
     * valid policy but certain Cloud Platform services (such as Projects)
     * might reject them.
     * </pre>
     *
     * <code>.google.iam.v1.Policy policy = 2;</code>
     */
    public function setPolicy(&$var)
    {
        GPBUtil::checkMessage($var, \Google\Cloud\Iam\V1\Policy::class);
        $this->policy = $var;

        return $this;
    }

}
