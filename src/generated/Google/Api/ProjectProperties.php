<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/consumer.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * A descriptor for defining project properties for a service. One service may
 * have many consumer projects, and the service may want to behave differently
 * depending on some properties on the project. For example, a project may be
 * associated with a school, or a business, or a government agency, a business
 * type property on the project may affect how a service responds to the client.
 * This descriptor defines which properties are allowed to be set on a project.
 * Example:
 *    project_properties:
 *      properties:
 *      - name: NO_WATERMARK
 *        type: BOOL
 *        description: Allows usage of the API without watermarks.
 *      - name: EXTENDED_TILE_CACHE_PERIOD
 *        type: INT64
 * </pre>
 *
 * Protobuf type <code>google.api.ProjectProperties</code>
 */
class ProjectProperties extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * List of per consumer project-specific properties.
     * </pre>
     *
     * <code>repeated .google.api.Property properties = 1;</code>
     */
    private $properties;

    public function __construct() {
        \GPBMetadata\Google\Api\Consumer::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * List of per consumer project-specific properties.
     * </pre>
     *
     * <code>repeated .google.api.Property properties = 1;</code>
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * <pre>
     * List of per consumer project-specific properties.
     * </pre>
     *
     * <code>repeated .google.api.Property properties = 1;</code>
     */
    public function setProperties(&$var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Api\Property::class);
        $this->properties = $arr;
    }

}

