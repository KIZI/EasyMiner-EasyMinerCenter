<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbDatasource - třída pro zabalení datového zdroje z datové služby
 * @package EasyMinerCenter\Model\Data\Databases\DataService
 * @author Stanislav Vojíř
 * @property int|null $id
 * @property string $name
 * @property string $type m:Enum("limited","unlimited")
 * @property int|null $size - počet instancí
 */
class DbDatasource {
  public $id;
  public $name;
  public $type;
  public $size;

  /**
   * @param int|null $id
   * @param string $name
   * @param string $type
   * @param int|null $size = null
   */
  public function __construct($id,$name,$type,$size=null) {
    $this->id=$id;
    $this->name=$name;
    $this->type=$type;
    $this->size=$size;
  }
}