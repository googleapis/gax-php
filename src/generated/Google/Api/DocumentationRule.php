<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/documentation.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * A documentation rule provides information about individual API elements.
 * </pre>
 *
 * Protobuf type <code>google.api.DocumentationRule</code>
 */
class DocumentationRule extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
     * </pre>
     *
     * <code>string selector = 1;</code>
     */
    private $selector = '';
    /**
     * <pre>
     * Description of the selected API(s).
     * </pre>
     *
     * <code>string description = 2;</code>
     */
    private $description = '';
    /**
     * <pre>
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     * </pre>
     *
     * <code>string deprecation_description = 3;</code>
     */
    private $deprecation_description = '';

    public function __construct() {
        \GPBMetadata\Google\Api\Documentation::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
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
     * The selector is a comma-separated list of patterns. Each pattern is a
     * qualified name of the element which may end in "*", indicating a wildcard.
     * Wildcards are only allowed at the end and for a whole component of the
     * qualified name, i.e. "foo.*" is ok, but not "foo.b*" or "foo.*.bar". To
     * specify a default for all applicable elements, the whole pattern "*"
     * is used.
     * </pre>
     *
     * <code>string selector = 1;</code>
     */
    public function setSelector($var)
    {
        GPBUtil::checkString($var, True);
        $this->selector = $var;
    }

    /**
     * <pre>
     * Description of the selected API(s).
     * </pre>
     *
     * <code>string description = 2;</code>
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * <pre>
     * Description of the selected API(s).
     * </pre>
     *
     * <code>string description = 2;</code>
     */
    public function setDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->description = $var;
    }

    /**
     * <pre>
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     * </pre>
     *
     * <code>string deprecation_description = 3;</code>
     */
    public function getDeprecationDescription()
    {
        return $this->deprecation_description;
    }

    /**
     * <pre>
     * Deprecation description of the selected element(s). It can be provided if an
     * element is marked as `deprecated`.
     * </pre>
     *
     * <code>string deprecation_description = 3;</code>
     */
    public function setDeprecationDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->deprecation_description = $var;
    }

}

