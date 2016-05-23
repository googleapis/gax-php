<?php
// DO NOT EDIT! Generated by Protobuf-PHP protoc plugin 1.0
// Source: google/api/billing.proto
//   Date: 2016-05-23 22:01:42

namespace google\api {

  class Billing extends \DrSlump\Protobuf\Message {

    /**  @var string[]  */
    public $metrics = array();
    
    /**  @var \google\api\BillingStatusRule[]  */
    public $rules = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'google.api.Billing');

      // REPEATED STRING metrics = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "metrics";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $descriptor->addField($f);

      // REPEATED MESSAGE rules = 5
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 5;
      $f->name      = "rules";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $f->reference = '\google\api\BillingStatusRule';
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <metrics> has a value
     *
     * @return boolean
     */
    public function hasMetrics(){
      return $this->_has(1);
    }
    
    /**
     * Clear <metrics> value
     *
     * @return \google\api\Billing
     */
    public function clearMetrics(){
      return $this->_clear(1);
    }
    
    /**
     * Get <metrics> value
     *
     * @param int $idx
     * @return string
     */
    public function getMetrics($idx = NULL){
      return $this->_get(1, $idx);
    }
    
    /**
     * Set <metrics> value
     *
     * @param string $value
     * @return \google\api\Billing
     */
    public function setMetrics( $value, $idx = NULL){
      return $this->_set(1, $value, $idx);
    }
    
    /**
     * Get all elements of <metrics>
     *
     * @return string[]
     */
    public function getMetricsList(){
     return $this->_get(1);
    }
    
    /**
     * Add a new element to <metrics>
     *
     * @param string $value
     * @return \google\api\Billing
     */
    public function addMetrics( $value){
     return $this->_add(1, $value);
    }
    
    /**
     * Check if <rules> has a value
     *
     * @return boolean
     */
    public function hasRules(){
      return $this->_has(5);
    }
    
    /**
     * Clear <rules> value
     *
     * @return \google\api\Billing
     */
    public function clearRules(){
      return $this->_clear(5);
    }
    
    /**
     * Get <rules> value
     *
     * @param int $idx
     * @return \google\api\BillingStatusRule
     */
    public function getRules($idx = NULL){
      return $this->_get(5, $idx);
    }
    
    /**
     * Set <rules> value
     *
     * @param \google\api\BillingStatusRule $value
     * @return \google\api\Billing
     */
    public function setRules(\google\api\BillingStatusRule $value, $idx = NULL){
      return $this->_set(5, $value, $idx);
    }
    
    /**
     * Get all elements of <rules>
     *
     * @return \google\api\BillingStatusRule[]
     */
    public function getRulesList(){
     return $this->_get(5);
    }
    
    /**
     * Add a new element to <rules>
     *
     * @param \google\api\BillingStatusRule $value
     * @return \google\api\Billing
     */
    public function addRules(\google\api\BillingStatusRule $value){
     return $this->_add(5, $value);
    }
  }
}

namespace google\api {

  class BillingStatusRule extends \DrSlump\Protobuf\Message {

    /**  @var string */
    public $selector = null;
    
    /**  @var string[]  */
    public $allowed_statuses = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'google.api.BillingStatusRule');

      // OPTIONAL STRING selector = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "selector";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // REPEATED STRING allowed_statuses = 2
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 2;
      $f->name      = "allowed_statuses";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <selector> has a value
     *
     * @return boolean
     */
    public function hasSelector(){
      return $this->_has(1);
    }
    
    /**
     * Clear <selector> value
     *
     * @return \google\api\BillingStatusRule
     */
    public function clearSelector(){
      return $this->_clear(1);
    }
    
    /**
     * Get <selector> value
     *
     * @return string
     */
    public function getSelector(){
      return $this->_get(1);
    }
    
    /**
     * Set <selector> value
     *
     * @param string $value
     * @return \google\api\BillingStatusRule
     */
    public function setSelector( $value){
      return $this->_set(1, $value);
    }
    
    /**
     * Check if <allowed_statuses> has a value
     *
     * @return boolean
     */
    public function hasAllowedStatuses(){
      return $this->_has(2);
    }
    
    /**
     * Clear <allowed_statuses> value
     *
     * @return \google\api\BillingStatusRule
     */
    public function clearAllowedStatuses(){
      return $this->_clear(2);
    }
    
    /**
     * Get <allowed_statuses> value
     *
     * @param int $idx
     * @return string
     */
    public function getAllowedStatuses($idx = NULL){
      return $this->_get(2, $idx);
    }
    
    /**
     * Set <allowed_statuses> value
     *
     * @param string $value
     * @return \google\api\BillingStatusRule
     */
    public function setAllowedStatuses( $value, $idx = NULL){
      return $this->_set(2, $value, $idx);
    }
    
    /**
     * Get all elements of <allowed_statuses>
     *
     * @return string[]
     */
    public function getAllowedStatusesList(){
     return $this->_get(2);
    }
    
    /**
     * Add a new element to <allowed_statuses>
     *
     * @param string $value
     * @return \google\api\BillingStatusRule
     */
    public function addAllowedStatuses( $value){
     return $this->_add(2, $value);
    }
  }
}

