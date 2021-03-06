<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpAttribute - class representing an attribute on preprocessing service
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
  public $type;
  public $uniqueValuesSize;

  /**
   * @param int $id
   * @param int $dataset
   * @param int $field
   * @param string $name
   * @param string $type
   * @param int $uniqueValuesSize
   */
  public function __construct($id, $dataset, $field, $name, $type, $uniqueValuesSize){
    $this->id=$id;
    $this->dataset=$dataset;
    $this->field=$field;
    $this->name=$name;
    $this->type=$type;
    $this->uniqueValuesSize=$uniqueValuesSize;
  }
}