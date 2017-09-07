<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbValuesStats - class representing statistics of values of a database column (DbField)
 * @package EasyMinerCenter\Model\Data\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property float $min
 * @property float $max
 * @property float $avg
 */
class DbValuesStats{

  public $min;
  public $max;
  public $avg;

  /**
   * @param float $min
   * @param float $max
   * @param float $avg
   */
  public function __construct($min, $max, $avg){
    $this->min=$min;
    $this->max=$max;
    $this->avg=$avg;
  }

}