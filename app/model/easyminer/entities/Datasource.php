<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use LeanMapper\Entity;

/**
 * Class Datasource
 *
*@package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $datasourceId = null
 * @property User|null $user = null m:hasOne
 * @property string $type = m:Enum('mysql','limited','unlimited')
 * @property string|null $name = null
 * @property int|null $dbDatasourceId = null
 * @property bool $available = true
 * @property string|null $dbServer = null
 * @property string|null $dbApi = null
 * @property int|null $dbPort = null
 * @property string $dbUsername
 * @property string $dbName
 * @property string $dbTable
 * @property int|null $size = null
 * @property-read DatasourceColumn[] $datasourceColumns m:belongsToMany
 * @property-read DbConnection $dbDonnection
 */
class Datasource extends Entity{
  /**
   * Funkce vracející přehled typů databází
   * @return array
   */
  public static function getTypes(){//TODO remove?
    return array(
      'mysql'=>'MySQL',
      'limited'=>'Data service limited',
      'unlimited'=>'Data service unlimited',
    );
  }

  /**
   * Funkce vracející základní parametry datového zdroje
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->datasourceId,
      'type'=>$this->type,
      'name'=>$this->getName(),
      'dbDatasourceId'=>$this->dbDatasourceId,
      'available'=>$this->available,
      'size'=>$this->size
    ];
  }

  /**
   * Funkce vracející název datového zdroje (pokud není definován, je vrácen název tabulky)
   * @return string
   */
  public function getName() {
    if (!empty($this->row->name)){
      return $this->row->name;
    }else{
      return $this->dbTable;
    }
  }

  /**
   * Funkce pro připravení entity nového datového zdroje podle DbConnection
   *
   * @param DbConnection $dbConnection
   * @return Datasource
   */
  public static function newFromDbConnection(DbConnection $dbConnection) {
    $datasource = new Datasource();
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
    if (empty($this->row->db_password)){return null;}
    return StringsHelper::decodePassword($this->row->db_password);
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    $this->row->db_password=StringsHelper::encodePassword($password);
  }
} 