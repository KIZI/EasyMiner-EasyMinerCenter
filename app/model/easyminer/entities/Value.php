<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Value
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property string $valueId
 * @property Format|null $format m:hasOne
 * @property string $value
 */
class Value extends Entity{

  public function __toString(){
    return $this->value;
  }
  
} 