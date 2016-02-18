<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbColumnValuesStatistic - informace o statistice vypočtené na základě databázového sloupce
 * @package EasyMinerCenter\Model\Data\Entities
 */
class OLD_DbColumnValuesStatistic extends DbColumn{

  /** @var null|float $minValue */
  public $minValue    = null;
  /** @var null|float $maxValue */
  public $maxValue    = null;
  /** @var null|float $avgValue */
  public $avgValue    = null;

  /** @var null|int $rowsCount */
  public $rowsCount   = null;
  /** @var null|int $valuesCount */
  public $valuesCount = null;

  /** @var array|null $valuesArr - pole s hodnotami z daného DB sloupce */
  public $valuesArr=null;

  public function __construct($dbColumn=null){
    if ($dbColumn instanceof DbColumn){
      $this->dataType=$dbColumn->dataType;
      $this->name=$dbColumn->name;
      $this->strLength=$dbColumn->strLength;
    }
  }

} 