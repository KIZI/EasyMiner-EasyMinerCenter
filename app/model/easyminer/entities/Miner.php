<?php

namespace App\Model\EasyMiner\Entities;

use Nette;

/**
 * Class Miner
 * @package App\Model\EasyMiner\Entities
 * @property int $idMiner
 * @property int $idUser
 * @property string $name
 * @property string $type = 'lm'
 * @property int $idDatasource
 */
class Miner extends BaseEntity{


  /**
   * Funkce pro vygenerování pole s daty pro uložení do DB
   * @param bool $includeId = false
   * @return array
   */
  public function getDataArr($includeId = false) {
    $arr = array(
      'id_user'=>$this->idUser,
      'name'=>$this->name,
      'type'=>$this->type,
      'id_datasource'=>$this->idDatasource
    );
    if ($includeId){
      $arr['id_miner']=$this->idMiner;
    }
    return $arr;
  }

  /**
   * Funkce pro naplnění objektu daty z DB či z pole
   * @param $data
   */
  public function loadDataArr($data) {
    $this->idMiner=$data['id_miner'];

    $this->idUser=$data['id_user'];
    $this->name=$data['name'];
    $this->type=$data['type'];
    $this->idDatasource=$data['id_datasource'];
  }
}