<?php

declare(strict_types=1);

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: Testing/mocks.proto

namespace Google\ApiCore\Testing;

use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>google.apicore.testing.MockResponse</code>
 *
 * @internal
 */
class MockResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>uint64 number = 2;</code>
     */
    protected $number = 0;
    /**
     * Generated from protobuf field <code>repeated string resources_list = 3;</code>
     */
    private $resources_list;
    /**
     * Generated from protobuf field <code>string next_page_token = 4;</code>
     */
    protected $next_page_token = '';
    /**
     * Generated from protobuf field <code>map<string, string> resources_map = 5;</code>
     */
    private $resources_map;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type int|string $number
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $resources_list
     *     @type string $next_page_token
     *     @type array|\Google\Protobuf\Internal\MapField $resources_map
     * }
     */
    public function __construct($data = null)
    {
        \GPBMetadata\ApiCore\Testing\Mocks::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, true);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 number = 2;</code>
     * @return int|string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Generated from protobuf field <code>uint64 number = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setNumber($var)
    {
        GPBUtil::checkUint64($var);
        $this->number = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated string resources_list = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getResourcesList()
    {
        return $this->resources_list;
    }

    /**
     * Generated from protobuf field <code>repeated string resources_list = 3;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setResourcesList($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->resources_list = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string next_page_token = 4;</code>
     * @return string
     */
    public function getNextPageToken()
    {
        return $this->next_page_token;
    }

    /**
     * Generated from protobuf field <code>string next_page_token = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setNextPageToken($var)
    {
        GPBUtil::checkString($var, true);
        $this->next_page_token = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>map<string, string> resources_map = 5;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getResourcesMap()
    {
        return $this->resources_map;
    }

    /**
     * Generated from protobuf field <code>map<string, string> resources_map = 5;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setResourcesMap($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::STRING);
        $this->resources_map = $arr;

        return $this;
    }

}
