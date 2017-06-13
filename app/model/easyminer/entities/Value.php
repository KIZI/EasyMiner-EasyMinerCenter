<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Value
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property string $valueId
 * @property Format|null $format m:hasOne
 * @property string $value
 */
class Value extends Entity{

  public function __toString(){
    return $this->value;
  }
  
} 