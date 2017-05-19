<?php

namespace EasyMinerCenter\Model\Mining\Entities;

/**
 * Class MinerOutliersTask - class representing a outlier mining task in remote miner
 * @package EasyMinerCenter\Model\Mining\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $id
 * @property int $dataset
 */
class MinerOutliersTask{
  public $id;
  public $dataset;

  /**
   * MinerOutliersTask constructor.
   * @param int $id
   * @param int $dataset
   */
  public function __construct($id, $dataset){
    $this->id=$id;
    $this->dataset=$dataset;
  }

}