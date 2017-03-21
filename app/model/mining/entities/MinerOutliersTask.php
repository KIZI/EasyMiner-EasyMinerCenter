<?php

namespace EasyMinerCenter\Model\Mining\Entities;

/**
 * Class MinerOutliersTask - třída prezentující úlohu pro dolování outlierů uloženou v rámci vzdáleného mineru
 * @package EasyMinerCenter\Model\Mining\Entities
 * @author Stanislav Vojíř
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