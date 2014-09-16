<?php

namespace App\Model\EasyMiner\Entities;

/**
 * Class Datasource
 * @package App\Model\EasyMiner\Entities
 * @property int $idDatasource
 * @property int $idUser
 * @property string $type = 'mysql'
 * @property string $dbServer
 * @property string $dbPort
 * @property string $dbUsername
 * @property string $dbPassword
 * @property string $dbName
 * @property string $dbTable
 */
class Datasource extends BaseEntity{

  /**
   * Funkce pro vygenerování pole s daty pro uložení do DB
   * @param bool $includeId = false
   * @return array
   */
  public function getDataArr($includeId = false) {
    $arr = array(
      'id_user'=>$this->idUser,
      'type'=>$this->type,
      'db_server'=>$this->dbServer,
      'db_port'=>$this->dbPort,
      'db_username'=>$this->dbUsername,
      'db_password'=>$this->dbPassword,
      'db_name'=>$this->dbName,
      'db_table'=>$this->dbTable,
    );
    if ($includeId){
      $arr['id_datasource']=$this->idDatasource;
    }
    return $arr;
  }

  /**
   * Funkce pro naplnění objektu daty z DB či z pole
   * @param $data
   */
  public function loadDataArr($data) {
    $this->idDatasource=$data['id_datasource'];

    $this->idUser=$data['id_user'];
    $this->type=$data['type'];
    $this->dbServer=$data['db_server'];
    $this->dbPort=$data['db_port'];
    $this->dbUsername=$data['db_username'];
    $this->dbPassword=$data['db_password'];
    $this->dbName=$data['db_name'];
    $this->dbTable=$data['db_table'];
  }
} 