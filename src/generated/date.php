<?php
// DO NOT EDIT! Generated by Protobuf-PHP protoc plugin 1.0
// Source: google/type/date.proto
//   Date: 2016-07-18 20:45:47

namespace google\type {

  class Date extends \DrSlump\Protobuf\Message {

    /**  @var int */
    public $year = null;
    
    /**  @var int */
    public $month = null;
    
    /**  @var int */
    public $day = null;
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'google.type.Date');

      // OPTIONAL INT32 year = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "year";
      $f->type      = \DrSlump\Protobuf::TYPE_INT32;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL INT32 month = 2
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 2;
      $f->name      = "month";
      $f->type      = \DrSlump\Protobuf::TYPE_INT32;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL INT32 day = 3
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 3;
      $f->name      = "day";
      $f->type      = \DrSlump\Protobuf::TYPE_INT32;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <year> has a value
     *
     * @return boolean
     */
    public function hasYear(){
      return $this->_has(1);
    }
    
    /**
     * Clear <year> value
     *
     * @return \google\type\Date
     */
    public function clearYear(){
      return $this->_clear(1);
    }
    
    /**
     * Get <year> value
     *
     * @return int
     */
    public function getYear(){
      return $this->_get(1);
    }
    
    /**
     * Set <year> value
     *
     * @param int $value
     * @return \google\type\Date
     */
    public function setYear( $value){
      return $this->_set(1, $value);
    }
    
    /**
     * Check if <month> has a value
     *
     * @return boolean
     */
    public function hasMonth(){
      return $this->_has(2);
    }
    
    /**
     * Clear <month> value
     *
     * @return \google\type\Date
     */
    public function clearMonth(){
      return $this->_clear(2);
    }
    
    /**
     * Get <month> value
     *
     * @return int
     */
    public function getMonth(){
      return $this->_get(2);
    }
    
    /**
     * Set <month> value
     *
     * @param int $value
     * @return \google\type\Date
     */
    public function setMonth( $value){
      return $this->_set(2, $value);
    }
    
    /**
     * Check if <day> has a value
     *
     * @return boolean
     */
    public function hasDay(){
      return $this->_has(3);
    }
    
    /**
     * Clear <day> value
     *
     * @return \google\type\Date
     */
    public function clearDay(){
      return $this->_clear(3);
    }
    
    /**
     * Get <day> value
     *
     * @return int
     */
    public function getDay(){
      return $this->_get(3);
    }
    
    /**
     * Set <day> value
     *
     * @param int $value
     * @return \google\type\Date
     */
    public function setDay( $value){
      return $this->_set(3, $value);
    }
  }
}

