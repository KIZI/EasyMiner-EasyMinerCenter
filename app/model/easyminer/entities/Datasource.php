<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use LeanMapper\Entity;

/**
 * Class Datasource
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $datasourceId = null
 * @property User|null $user = null m:hasOne
 * @property string $type = m:Enum('mysql','limited','unlimited')
 * @property int|null $dbDatasourceId = null
 * @property bool $available = true
 * @property string|null $dbServer = null
 * @property string|null $dbApi = null
 * @property int|null $dbPort = null
 * @property string $dbUsername
 * @property string $dbName
 * @property string|null $name = null
 * @property int|null $size = null
 * @property-read DatasourceColumn[] $datasourceColumns m:belongsToMany
 * @property-read DbConnection $dbDonnection
 * @property-read string $dbTable
 */
class Datasource extends Entity{
  /**
   * Method returning an array with basic data properties of the given datasource
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->datasourceId,
      'type'=>$this->type,
      'name'=>$this->name,
      'dbDatasourceId'=>$this->dbDatasourceId,
      'available'=>$this->available,
      'size'=>$this->size
    ];
  }

  /**
   * Method returing a new Datasource using a DbConnection   *
   * @param DbConnection $dbConnection
   * @return Datasource
   */
  public static function newFromDbConnection(DbConnection $dbConnection) {
    $datasource = new Datasource();
    $datasource->type=$dbConnection->type;
    $datasource->dbName=$dbConnection->dbName;
    $datasource->dbServer=$dbConnection->dbServer;
    $datasource->dbApi = $dbConnection->dbApi;
    if (!empty($dbConnection->dbPort)){
      $datasource->dbPort=$dbConnection->dbPort;
    }
    $datasource->dbUsername=$dbConnection->dbUsername;
    $datasource->setDbPassword($dbConnection->dbPassword);
    return $datasource;
  }

  /**
   * @return DbConnection
   */
  public function getDbConnection(){
    $dbConnection=new DbConnection();
    $dbConnection->dbName=$this->dbName;
    $dbConnection->dbUsername=$this->dbUsername;
    $dbConnection->dbPassword=$this->getDbPassword();
    $dbConnection->dbPort=$this->dbPort;
    $dbConnection->dbApi=$this->dbApi;
    $dbConnection->dbServer=$this->dbServer;
    $dbConnection->type=$this->type;
    return $dbConnection;
  }

  /**
   * @return string
   */
  public function getDbPassword(){
    /** @noinspection PhpUndefinedFieldInspection */
    if (!empty($this->row->db_password)){
      /** @noinspection PhpUndefinedFieldInspection */
      return StringsHelper::decodePassword($this->row->db_password);
    }else{
      return null;
    }
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    /** @noinspection PhpUndefinedFieldInspection */
    $this->row->db_password=StringsHelper::encodePassword($password);
  }

  /**
   * @return string
   */
  public function getDbTable() {
    /** @noinspection PhpUndefinedFieldInspection */
    return @$this->row->name;
  }
} 