<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbValue - class representing one value of a database column (DbField)
 * @package EasyMinerCenter\Model\Data\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $id
 * @property int $frequency
 * @property string $value
 */
class DbValue{

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