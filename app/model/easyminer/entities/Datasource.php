<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use LeanMapper\Entity;

/**
 * Class Datasource
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $datasourceId = null
 * @property User|null $user = null m:hasOne
 * @property string $type = m:Enum('mysql','cassandra')
 * @property string $dbServer
 * @property int|null $dbPort = null
 * @property string $dbUsername
 * @property string $dbName
 * @property string $dbTable
 * @property-read DatasourceColumn[] $datasourceColumns m:belongsToMany
 * @property-read DbConnection $dbDonnection
 */
class Datasource extends Entity{
  /**
   * Funkce vracející přehled typů databází
   * @return array
   */
  public static function getTypes(){
    return array(
      'mysql'=>'MySQL',
      'cassandra'=>'Cassandra DB'
    );
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