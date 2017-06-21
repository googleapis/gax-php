<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/config_change.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated advice about this change, used for providing more
 * information about how a change will affect the existing service.
 *
 * Protobuf type <code>Google\Api\Advice</code>
 */
class Advice extends \Google\Protobuf\Internal\Message
{
    /**
     * Useful description for why this advice was applied and what actions should
     * be taken to mitigate any implied risks.
     *
     * Generated from protobuf field <code>string description = 2;</code>
     */
    private $description = '';

    public function __construct() {
        \GPBMetadata\Google\Api\ConfigChange::initOnce();
        parent::__construct();
    }

    /**
     * Useful description for why this advice was applied and what actions should
     * be taken to mitigate any implied risks.
     *
     * Generated from protobuf field <code>string description = 2;</code>
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Useful description for why this advice was applied and what actions should
     * be taken to mitigate any implied risks.
     *
     * Generated from protobuf field <code>string description = 2;</code>
     * @param string $var
     */
    public function setDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->description = $var;
    }

}

