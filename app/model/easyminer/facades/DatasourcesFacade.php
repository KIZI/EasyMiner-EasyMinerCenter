<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\Data\Entities\DbConnection;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

class DatasourcesFacade {
  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var array $databasesConfig - konfigurace jednotlivých připojení k DB */
  private $databasesConfig;

  /**
   * @param array $databasesConfig
   * @param DatasourcesRepository $datasourcesRepository
   */
  public function __construct($databasesConfig, DatasourcesRepository $datasourcesRepository, DatabasesFacade $databasesFacade){
    $this->datasourcesRepository=$datasourcesRepository;
    $this->databasesConfig=$databasesConfig;
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * @param int $id
   * @return Datasource
   */
  public function findDatasource($id){
    return $this->datasourcesRepository->find($id);
  }

  /**
   * @param int|User $user
   * @return Datasource[]|null
   */
  public function findDatasourcesByUser($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $this->datasourcesRepository->findAllBy(array('user_id'=>$user));
  }



  /**
   * @param Datasource $datasource
   * @return bool
   */
  public function saveDatasource(Datasource &$datasource){
    $this->datasourcesRepository->persist($datasource);
  }

  /**
   * @param Datasource|int $datasource
   * @return int
   */
  public function deleteDatasource($datasource){
    if (!($datasource instanceof Datasource)){
      $datasource=$this->datasourcesRepository->find($datasource);
    }
    return $this->datasourcesRepository->delete($datasource);
  }


  /**
   * Funkce pro připravení parametrů nového datového zdroje pro daného uživatele...
   * @param User $user
   * @param string $dbType
   * @throws BadRequestException
   * @return Datasource
   */
  public function prepareNewDatasourceForUser(User $user,$dbType){
    $datasource=new Datasource();
    if (!in_array($dbType,$this->databasesFacade->getDatabaseTypes()) || !isset($this->databasesConfig[$dbType])){
      throw new BadRequestException('Unsupported type of database!',500);
    }
    $databaseConfig=$this->databasesConfig[$dbType];

    $datasource->type=$dbType;
    $datasource->user=$user;
    $datasource->dbName=str_replace('*',$user->userId,$databaseConfig['_database']);
    $datasource->dbUsername=str_replace('*',$user->userId,$databaseConfig['_username']);
    $datasource->setDbPassword($this->getUserDbPassword($user));
    $datasource->dbServer=$databaseConfig['server'];
    if (!empty($databaseConfig['port'])){
      $datasource->dbPort=$databaseConfig['port'];
    }

    $dbConnection=$datasource->getDbConnection();

    try{
      $this->databasesFacade->openDatabase($dbConnection);
    }catch (\Exception $e){
      //pokud došlo k chybě, pokusíme se vygenerovat uživatelský účet a databázi
      $this->databasesFacade->openDatabase($this->getAdminDbConnection($dbType));
      if (!$this->databasesFacade->createUserDatabase($dbConnection)){
        throw new \Exception('Database creation failed!');
      }
    }
    return $datasource;
  }

  /**
   * Funkce vracející heslo k DB na základě údajů klienta
   * @param User $user
   * @return string
   */
  private function getUserDbPassword(User $user){
    return Strings::substring($user->getDbPassword(),2,3).Strings::substring(sha1($user->userId.$user->getDbPassword()),4,5);
  }

  /**
   * Funkce vracející admin přístupy k DB daného typu
   * @param string $dbType
   * @return DbConnection
   */
  private function getAdminDbConnection($dbType){
    $dbConnection=new DbConnection();
    $databaseConfig=$this->databasesConfig[$dbType];
    $dbConnection->type=$dbType;
    $dbConnection->dbUsername=$databaseConfig['username'];
    $dbConnection->dbPassword=$databaseConfig['password'];
    $dbConnection->dbServer=$databaseConfig['server'];
    if (!empty($databaseConfig['port'])){
      $dbConnection->dbPort=$databaseConfig['port'];
    }
    return $dbConnection;
  }

} 