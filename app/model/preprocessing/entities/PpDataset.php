<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpDataset - třída představující předzpracovanou datovou tabulku
 *
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 *
 * @property $id
 * @property $name
 * @property $dataSource
 * @property $type
 * @property int|null $size - počet instancí
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