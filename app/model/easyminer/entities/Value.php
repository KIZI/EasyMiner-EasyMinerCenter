<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Value
 * @package App\Model\EasyMiner\Entities
 * @property string $valueId
 * @property Format $format m:hasOne
 * @property string $value
 */
class Value extends Entity{

  public function __toString(){
    return $this->value;
  }
  
} 