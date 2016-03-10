<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases;

use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class DatabaseFactory - Factory třída pro vytváření připojení k DB
 *
*@package EasyMinerCenter\Model\Data\Databases
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
  /** @var  array $preprocessingDatabasesConfig - pole s konfigurací přístupů k jednotlivým typům databází*/
  private $preprocessingDatabasesConfig;

  /**
   * @param array $databasesConfig
   */
  public function __construct($databasesConfig) {
    $this->preprocessingDatabasesConfig=$databasesConfig;
  }

  /**
   * Funkce vracející informace o nakonfigurovaných databázích
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
   * Funkce vracející název dabáze zvoleného typu
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
   * Funkce vracející výchozí připojení k databázi
   * @param string $ppType
   * @param User $user
   * @return PpConnection
   */
  public function getDefaultPpConnection($ppType, User $user) {
    $config=$this->getPreprocessingDatabaseConfig($ppType);
    $ppConnection = new PpConnection();
    $ppConnection->type=$ppType;
    $ppConnection->dbApi=!empty($config['api'])?$config['api']:null;
    $ppConnection->dbServer=!empty($config['server'])?$config['server']:null;
    $ppConnection->dbPort=!empty($config['port'])?$config['port']:null;
    //konfigurace připojení k DB
    $ppConnection->dbName=str_replace('*',$user->userId,$config['_database']);
    $ppConnection->dbUsername=str_replace('*',$user->userId,$config['_username']);
    //heslo nastavujeme, pokud pro daný typ databáze není nastaveno na FALSE
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
   * Funkce vracející připojení k databázi
   *
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
   * Funkce vracející název třídy obsahující příslušný ovladač databáze
   *
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
   * Funkce vracející výchozí připojení k databázi
   *
   * @param string $ppType
   * @param User $user
   * @return IPreprocessing
   */
  public function getPreprocessingInstanceWithDefaultPpConnection($ppType, User $user) {
    return $this->getPreprocessingInstance($this->getDefaultPpConnection($ppType, $user), $user);
  }


  //TODO doplnit další funkce...


  /**
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
   * Funkce vracející typ vhodného ovladače preprocessingu podle typu databáze, ve které jsou uložena data
   * @param string $dbType
   * @return string
   */
  public function getPreprocessingTypeByDatabaseType($dbType) {
    if (!empty(self::$dbPpTypesMapping[$dbType])){return self::$dbPpTypesMapping[$dbType];}
    throw new \InvalidArgumentException('Unsupported DB type: '.$dbType);
  }
}