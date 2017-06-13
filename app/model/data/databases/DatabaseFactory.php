<?php

namespace EasyMinerCenter\Model\Data\Databases;

use EasyMinerCenter\Model\Data\Databases\DataService\UnlimitedDatabase;
use EasyMinerCenter\Model\Data\Databases\MySQL\MySQLDatabaseConstructor;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class DatabaseFactory
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * xxx
 */
class DatabaseFactory {
  const DB_AVAILABILITY_CHECK_INTERVAL=600;//interval mezi kontrolami přístupu k DB (v sekundách)

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
   * Funkce vracející název dabáze zvoleného typu
   * @param string $dbType
   * @return string
   * @throws \Exception
   */
  public function getDbTypeName($dbType) {
    /** @var IDatabase|string $dbClass */
    $dbClass=self::getDatabaseClassName($dbType);
    return $dbClass::getDbTypeName();
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
   * Funkce vracející administrátorské připojení k databázi
   * @param string $dbType
   * @return DbConnection
   */
  public function getAdminDbConnection($dbType) {
    $config=$this->getDatabaseConfig($dbType);
    $dbConnection = new DbConnection();
    $dbConnection->type=$dbType;
    $dbConnection->dbApi=!empty($config['api'])?$config['api']:null;
    $dbConnection->dbServer=!empty($config['server'])?$config['server']:null;
    $dbConnection->dbPort=!empty($config['port'])?$config['port']:null;
    //konfigurace připojení k DB
    $dbConnection->dbName=null;
    $dbConnection->dbUsername=@$config['username'];
    $dbConnection->dbPassword=@$config['password'];
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
    $dbClass=self::getDatabaseClassName($dbConnection->type);
    return new $dbClass($dbConnection, $user->getEncodedApiKey());
  }

  /**
   * Funkce vracející název třídy obsahující příslušný ovladač databáze
   * @param string $dbType
   * @return string
   * @throws \Exception
   */
  private static function getDatabaseClassName($dbType) {
    if (empty(self::$dbTypeClasses[$dbType])){
      throw new \Exception('Database driver "'.$dbType.'" not found!');
    }
    return self::$dbTypeClasses[$dbType];
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

  /**
   * Funkce pro kontrolu dostupnosti databáze a její případné vytvoření
   * @param DbConnection $dbConnection
   * @param User $user
   * @return bool
   */
  public function checkDatabaseAvailability(DbConnection $dbConnection,/** @noinspection PhpUnusedParameterInspection */ User $user){
    if ($dbConnection->type==UnlimitedDatabase::DB_TYPE){
      return true;
    }elseif(MySQLDatabaseConstructor::isDatabaseAvailable($dbConnection)){
      return true;
    }else{
      //vytvoření nové DB
      $mysqlDatabaseConstructor=new MySQLDatabaseConstructor($this->getAdminDbConnection($dbConnection->type));
      $result=$mysqlDatabaseConstructor->createUserDatabase($dbConnection);
      unset($mysqlDatabaseConstructor);
      sleep(1);//zpoždění, aby byla dokončena inicializace uživatelského účtu v DB
      return $result;
    }
  }

}