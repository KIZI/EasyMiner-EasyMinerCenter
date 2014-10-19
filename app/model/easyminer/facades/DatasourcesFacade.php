<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbConnection;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\DatasourceColumnsRepository;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

class DatasourcesFacade {
  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  DatasourceColumnsRepository $datasourceColumnsRepository */
  private $datasourceColumnsRepository;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var array $databasesConfig - konfigurace jednotlivých připojení k DB */
  private $databasesConfig;

  /**
   * @param array $databasesConfig
   * @param DatasourcesRepository $datasourcesRepository
   * @param DatasourceColumnsRepository $datasourceColumnsRepository
   * @param DatabasesFacade $databasesFacade
   */
  public function __construct($databasesConfig, DatasourcesRepository $datasourcesRepository, DatasourceColumnsRepository $datasourceColumnsRepository, DatabasesFacade $databasesFacade){
    $this->datasourcesRepository=$datasourcesRepository;
    $this->datasourceColumnsRepository=$datasourceColumnsRepository;
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
   * @param bool $reloadColumns = true - true, pokud má být zaktualizován seznam
   * @return bool
   */
  public function saveDatasource(Datasource &$datasource, $reloadColumns=true){
    $result=$this->datasourcesRepository->persist($datasource);
    if (!$reloadColumns){return $result;}
    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    $datasourceColumns=$datasource->datasourceColumns;
    $datasourceColumnsArr=array();
    if (!empty($datasourceColumns)){
      foreach ($datasourceColumns as $datasourceColumn){
        $datasourceColumnsArr[$datasourceColumn->name]=$datasourceColumn->name;
      }
    }
    /** @var DbColumn[] $dbColumns */
    $dbColumns=$this->databasesFacade->getColumns($datasource->dbTable);
    if (!empty($dbColumns)){
      foreach ($dbColumns as $dbColumn){
        if (isset($datasourceColumnsArr[$dbColumn->name])){
          unset($datasourceColumnsArr[$dbColumn->name]);
        }else{
          //vytvoříme info o datovém sloupci
          $datasourceColumn=new DatasourceColumn();
          $datasourceColumn->name=$dbColumn->name;
          $datasourceColumn->datasource=$datasource;
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    if (!empty($datasourceColumns)){
      foreach ($datasourceColumns as $datasourceColumn){
        //odmažeme info o sloupcích, které v datové tabulce již neexistují
        $this->datasourceColumnsRepository->delete($datasourceColumn);
      }
    }
    return $result;
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
   * @throws \Exception
   * @throws \Nette\Application\ApplicationException
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