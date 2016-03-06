<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpAttribute - třída představující datový sloupec (atribut) v tabulce předzpracovaných dat
 *
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 *
 * @property int $id
 * @property int $dataset
 * @property int $field
 * @property string $name
 * @property string $type
 * @property int $uniqueValuesSize
 */
class PpAttribute {
  const TYPE_NOMINAL = 'nominal';
  const TYPE_NUMERIC = 'numeric';

  public $id;
  public $dataset;
  public $field;
  public $name;
  public $uniqueValuesSize;

  /**
   * @param int $id
   * @param int $dataset
   * @param int $field
   * @param string $name
   * @param int $uniqueValuesSize
   */
  public function __construct($id, $dataset, $field, $name, $uniqueValuesSize){
    $this->id=$id;
    $this->dataset=$dataset;
    $this->field=$field;
    $this->name=$name;
    $this->uniqueValuesSize=$uniqueValuesSize;
  }
}