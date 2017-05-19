<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpDataset - class representing one preprocessed dataset on preprocessing service
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $id
 * @property string $name
 * @property int $dataSource
 * @property string $type
 * @property int|null $size - count of rows
 */
class PpDataset {

  public $id;
  public $name;
  public $dataSource;
  public $type;
  public $size = null;

  /**
   * @param int $id
   * @param string $name
   * @param int $dataSource
   * @param string $type
   * @param null|int $size = null
   */
  public function __construct($id, $name, $dataSource, $type, $size=null){
    $this->id=$id;
    $this->name=$name;
    $this->dataSource=$dataSource;
    $this->type=$type;
    $this->size=$size;
  }

}