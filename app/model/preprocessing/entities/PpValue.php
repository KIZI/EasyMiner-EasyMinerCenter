<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpValue - class representing one value of a preprocessed attribute
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $id
 * @property int $frequency
 * @property string $value
 */
class PpValue{

  public $id;
  public $frequency;
  public $value;

  /**
   * @param $id
   * @param $value
   * @param $frequency
   */
  public function __construct($id, $value, $frequency){
    $this->id=$id;
    $this->frequency=$frequency;
    $this->value=$value;
  }

}