<?php

namespace App\Model\EasyMiner\Entities;
use App\Model\Data\Entities\DbConnection;
use LeanMapper\Entity;

/**
 * Class Datasource
 * @package App\Model\EasyMiner\Entities
 * @property int|null $datasourceId = null
 * @property int|null $userId = null
 * @property string $type = m:Enum('mysql','cassandra')
 * @property string $dbServer
 * @property string $dbPort
 * @property string $dbUsername
 * @property string $dbPassword
 * @property string $dbName
 * @property string $dbTable
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
    $dbConnection->dbPassword=$this->dbPassword;
    $dbConnection->dbPort=$this->dbPort;
    $dbConnection->dbServer=$this->dbServer;
    $dbConnection->type=$this->type;
    return $dbConnection;
  }
} 