<?php

namespace EasyMinerCenter\Model\Data\Databases;

use EasyMinerCenter\Model\Data\Entities\DbConnection;

/**
 * Class DatabaseFactory - Factory třída pro vytváření připojení k DB
 *
*@package EasyMinerCenter\Model\Data\Databases
 */
class DatabaseFactory {
  /** @var array $dbTypeConfig - konfigurace jednotlivých tříd ovladačů databází */
  private static $dbTypeClasses=[
    DbConnection::TYPE_MYSQL=>'\EasyMinerCenter\Model\Data\Databases\MySQL\MySQLDatabase',
    DbConnection::TYPE_LIMITED=>'\EasyMinerCenter\Model\Data\Databases\DataService\LimitedDatabase',
    DbConnection::TYPE_UNLIMITED=>'\EasyMinerCenter\Model\Data\Databases\DataService\UnlimitedDatabase',
  ];
  /** @var  array $databasesConfig - pole s konfigurací přístupů k jednotlivým typům databází*/
  private $databasesConfig;

  /**
   * @param array $databasesConfig
   */
  public function __construct($databasesConfig) {
    $this->databasesConfig=$databasesConfig;
  }

  /**
   * Funcke vracející informace o nakonfigurovaných databázích
   * @return string[]
   */
  public function getDbTypes() {
    $result=[];
    foreach(self::$dbTypeClasses as $dbType=>$className){
      if (empty($this->databasesConfig[$dbType])){continue;}
      if (!empty($this->databasesConfig[$dbType]['server'])){
        $result[]=$dbType;
      }
    }
    return $result;
  }

  /**
   * Funkce vracející výchozí připojení k databázi
   * @param string $dbType
   * @return DbConnection
   */
  public function getDatabaseDefaultDbConnection($dbType) {
    //TODO implement
  }

  /**
   * Funkce vracející připojení k databázi
   * @param DbConnection $dbConnection
   * @param string $apiKey
   */
  public function getDatabaseInstance(DbConnection $dbConnection, $apiKey) {
    //TODO implement
  }

  /**
   * Funkce vracející výchozí připojení k databázi
   * @param string $dbType
   */
  public function getDatabaseDefaultInstance($dbType) {
    //TODO
  }

}