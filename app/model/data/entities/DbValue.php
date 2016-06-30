<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbValue - třída představující jednu hodnotu datového sloupce
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
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