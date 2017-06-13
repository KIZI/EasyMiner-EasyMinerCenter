<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases;

use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class PreprocessingFactory - class with factory methods returning instances of preprocessing drivers
 * @package EasyMinerCenter\Model\Preprocessing\Databases
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class PreprocessingFactory {
  private static $ppTypeClasses=[
    PpConnection::TYPE_MYSQL=>'\EasyMinerCenter\Model\Preprocessing\Databases\MySQL\MySQLDatabase',
    PpConnection::TYPE_LIMITED=>'\EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService\LimitedDatabase',
    PpConnection::TYPE_UNLIMITED=>'\EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService\UnlimitedDatabase',
  ];
  private static $dbPpTypesMapping=[
    DbConnection::TYPE_MYSQL=>PpConnection::TYPE_MYSQL,
    DbConnection::TYPE_LIMITED=>PpConnection::TYPE_LIMITED,
    DbConnection::TYPE_UNLIMITED=>PpConnection::TYPE_UNLIMITED,
  ];
  /** @var  array $preprocessingDatabasesConfig - array with configuration of preprocessing database types */
  private $preprocessingDatabasesConfig;

  /**
   * @param array $databasesConfig
   */
  public function __construct($databasesConfig) {
    $this->preprocessingDatabasesConfig=$databasesConfig;
  }

  /**
   * Method returning list of configured preprocessing databases
   * @return string[]
   */
  public function getPpTypes() {
    $result=[];
    foreach(self::$ppTypeClasses as $ppType=>$className){
      if (empty($this->preprocessingDatabasesConfig[$ppType])){continue;}
      if (!empty($this->preprocessingDatabasesConfig[$ppType]['server'])){
        $result[]=$ppType;
      }
    }
    return $result;
  }

  /**
   * Method returning name of selected preprocessing database type
   * @param string $ppType
   * @return string
   * @throws \Exception
   */
  public function getPpTypeName($ppType) {
    /** @var IPreprocessing|string $ppClass */
    $ppClass=self::getPreprocessingClassName($ppType);
    return $ppClass::getPpTypeName();
  }

  /**
   * Method returning default connection to preprocessing database
   * @param string $ppType
   * @param User $user
   * @return PpConnection
   */
  public function getDefaultPpConnection($ppType, User $user) {
    $config=$this->getPreprocessingDatabaseConfig($ppType);
    $ppConnection = new PpConnection();
    $ppConnection->type=$ppType;
    $ppConnection->dbApi=!empty($config['preprocessingApi'])?$config['preprocessingApi']:null;
    $ppConnection->dbServer=!empty($config['server'])?$config['server']:null;
    $ppConnection->dbPort=!empty($config['port'])?$config['port']:null;
    //DB connection config
    $ppConnection->dbName=str_replace('*',$user->userId,$config['_database']);
    $ppConnection->dbUsername=str_replace('*',$user->userId,$config['_username']);
    //we set the password only if it is not set to FALSE (for the given database type)
    if (isset($config['_password'])){
      if (!$config['_password']){
        $ppConnection->dbPassword='';
      }else{
        $ppConnection->dbPassword=$config['_password'];
      }
    }else{
      $ppConnection->dbPassword=$user->getDbPassword();
    }
    return $ppConnection;
  }

  /**
   * Factory method returning instance of preprocessing database driver
   * @param PpConnection $ppConnection
   * @param User $user
   * @return IPreprocessing
   * @throws \Exception
   */
  public function getPreprocessingInstance(PpConnection $ppConnection, User $user) {
    $ppClass=self::getPreprocessingClassName($ppConnection->type);
    return new $ppClass($ppConnection, $user->getEncodedApiKey());
  }

  /**
   * Method returning name of the class which is appropriate driver to selected preprocessing database
   * @param string $ppType
   * @return string
   * @throws \Exception
   */
  private static function getPreprocessingClassName($ppType) {
    if (empty(self::$ppTypeClasses[$ppType])){
      throw new \Exception('Preprocessing driver "'.$ppType.'" not found!');
    }
    return self::$ppTypeClasses[$ppType];
  }

  /**
   * Method returning config of selected preprocessing database
   * @param string $ppType
   * @return array
   * @throws \Exception
   */
  public function getPreprocessingDatabaseConfig($ppType) {
    if (!empty($this->preprocessingDatabasesConfig[$ppType])){
      return $this->preprocessingDatabasesConfig[$ppType];
    }
    throw new \Exception('Preprocessing '.$ppType.' is not configured!');
  }

  /**
   * Method returning appropriate type of driver to access preprocessing database dependently to database, where are the data
   * @param string $dbType
   * @return string
   */
  public function getPreprocessingTypeByDatabaseType($dbType) {
    if (!empty(self::$dbPpTypesMapping[$dbType])){return self::$dbPpTypesMapping[$dbType];}
    throw new \InvalidArgumentException('Unsupported DB type: '.$dbType);
  }
}