<?php

namespace EasyMinerCenter\Model\Data\Databases;

use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

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
   * Funkce vracející informace o nakonfigurovaných databázích
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
   * @param User $user
   * @return DbConnection
   */
  public function getDefaultDbConnection($dbType, User $user) {
    $config=$this->getDatabaseConfig($dbType);
    $dbConnection = new DbConnection();
    $dbConnection->type=$dbType;
    $dbConnection->dbApi=!empty($config['api'])?$config['api']:null;
    $dbConnection->dbServer=!empty($config['server'])?$config['server']:null;
    $dbConnection->dbPort=!empty($config['port'])?$config['port']:null;
    //konfigurace připojení k DB
    $dbConnection->dbName=str_replace('*',$user->userId,$config['_database']);
    $dbConnection->dbUsername=str_replace('*',$user->userId,$config['_username']);
    //heslo nastavujeme, pokud pro daný typ databáze není nastaveno na FALSE
    if (isset($config['_password'])){
      if (!$config['_password']){
        $dbConnection->dbPassword='';
      }else{
        $dbConnection->dbPassword=$config['_password'];
      }
    }else{
      $dbConnection->dbPassword=$user->getDbPassword();
    }
    return $dbConnection;
  }

  /**
   * Funkce vracející připojení k databázi
   *
   * @param DbConnection $dbConnection
   * @param User $user
   * @return IDatabase
   * @throws \Exception
   */
  public function getDatabaseInstance(DbConnection $dbConnection, User $user) {
    if (empty(self::$dbTypeClasses[$dbConnection->type])){
      throw new \Exception('Database driver "'.$dbConnection->type.'" not found!');
    }
    /** @var IDatabase $dbClass */
    $dbClass=self::$dbTypeClasses[$dbConnection->type];
    return new $dbClass($dbConnection, $user->getEncodedApiKey());
  }

  /**
   * Funkce vracející výchozí připojení k databázi
   *
   * @param string $dbType
   * @param User $user
   * @return IDatabase
   */
  public function getDatabaseInstanceWithDefaultDbConnection($dbType, User $user) {
    return $this->getDatabaseInstance($this->getDefaultDbConnection($dbType, $user), $user);
  }


  //TODO doplnit další funkce...


  /**
   * @param string $dbType
   * @return array
   * @throws \Exception
   */
  public function getDatabaseConfig($dbType) {
    if (!empty($this->databasesConfig[$dbType])){
      return $this->databasesConfig[$dbType];
    }
    throw new \Exception('Database '.$dbType.' is not configured!');
  }
}