<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/consumer.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Defines project properties.
 * API services can define properties that can be assigned to consumer projects
 * so that backends can perform response customization without having to make
 * additional calls or maintain additional storage. For example, Maps API
 * defines properties that controls map tile cache period, or whether to embed a
 * watermark in a result.
 * These values can be set via API producer console. Only API providers can
 * define and set these properties.
 * </pre>
 *
 * Protobuf type <code>google.api.Property</code>
 */
class Property extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The name of the property (a.k.a key).
     * </pre>
     *
     * <code>string name = 1;</code>
     */
    private $name = '';
    /**
     * <pre>
     * The type of this property.
     * </pre>
     *
     * <code>.google.api.Property.PropertyType type = 2;</code>
     */
    private $type = 0;
    /**
     * <pre>
     * The description of the property
     * </pre>
     *
     * <code>string description = 3;</code>
     */
    private $description = '';

    public function __construct() {
        \GPBMetadata\Google\Api\Consumer::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The name of the property (a.k.a key).
     * </pre>
     *
     * <code>string name = 1;</code>
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * <pre>
     * The name of the property (a.k.a key).
     * </pre>
     *
     * <code>string name = 1;</code>
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * <pre>
     * The type of this property.
     * </pre>
     *
     * <code>.google.api.Property.PropertyType type = 2;</code>
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * <pre>
     * The type of this property.
     * </pre>
     *
     * <code>.google.api.Property.PropertyType type = 2;</code>
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Api\Property_PropertyType::class);
        $this->type = $var;

        return $this;
    }

    /**
     * <pre>
     * The description of the property
     * </pre>
     *
     * <code>string description = 3;</code>
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * <pre>
     * The description of the property
     * </pre>
     *
     * <code>string description = 3;</code>
     */
    public function setDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->description = $var;

        return $this;
    }

}

