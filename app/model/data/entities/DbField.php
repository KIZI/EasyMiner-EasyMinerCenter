<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbField
 * @package EasyMinerCenter\Model\Data\Databases\DataService
 * @author Stanislav Vojíř
 * @property int $id
 * @property int $dataSource
 * @property string $name
 * @property string $type m:Enum("nominal","numeric")
 * @property int $uniqueValuesSize
 */
class DbField {
  const TYPE_NOMINAL = 'nominal';
  const TYPE_NUMERICAL = 'numerical';
  const TYPE_STRING = self::TYPE_NOMINAL;

  public $id;
  public $dataSource;
  public $name;
  public $type;
  public $uniqueValuesSize;

  /**
   * @param int $id
   * @param int $dataSource
   * @param string $name
   * @param string $type
   * @param int $uniqueValuesSize
   */
  public function __construct($id,$dataSource,$name,$type,$uniqueValuesSize) {
    $this->id=$id;
    $this->dataSource=$dataSource;
    $this->name=$name;
    $this->type=$type;
    $this->uniqueValuesSize=$uniqueValuesSize;
  }
}