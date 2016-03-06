<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourceColumnsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourcesRepository;
use Nette\Application\BadRequestException;

/**
 * Class DatasourcesFacade - fasáda pro práci s datovými zdroji
 *
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 */
class DatasourcesFacade {
  /** @var  DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  DatasourceColumnsRepository $datasourceColumnsRepository */
  private $datasourceColumnsRepository;
  /** @var string[] $dbTypesWithRemoteDatasources - seznam typů databází, ve kterých se nacházejí vzdálené datové zdroje */
  private static $dbTypesWithRemoteDatasources=[DbConnection::TYPE_LIMITED,DbConnection::TYPE_UNLIMITED];

  /**
   * Funkce vracející seznam aktivních (nakonfigurovaných) typů databází
   *
   * @param bool $withNames = false
   * @return array
   */
  public function getDbTypes($withNames=false) {
    $dbTypes=$this->databaseFactory->getDbTypes();
    $result=[];
    if (!empty($dbTypes)) {
      foreach($dbTypes as $dbType) {
        $result[$dbType]=($withNames?$this->databaseFactory->getDbTypeName($dbType):$dbType);
      }
    }
    return $result;
  }

  /**
   * Funkce vrace
   * @return string
   */
  public function getPreferredDbType() {
    //TODO implementovat načtení výchozího typu databáze z konfigurace
    return DbConnection::TYPE_LIMITED;
  }

  /**
   * Funkce pro aktualizaci informací o vzdálených datových zdrojích
   *
   * @param User $user
   */
  public function updateRemoteDatasourcesByUser(User $user){
    $supportedDbTypes = $this->databaseFactory->getDbTypes();
    #region připravení seznamu externích
    /** @var DbDatasource[] $dbDatasources */
    $dbDatasources=[];
    foreach(self::$dbTypesWithRemoteDatasources as $dbType){
      if (in_array($dbType,$supportedDbTypes)){
        //načteme datové zdroje ze vzdálené databáze
        $database = $this->databaseFactory->getDatabaseInstanceWithDefaultDbConnection($dbType, $user);
        $remoteDbDatasources = $database->getDbDatasources();
        if (!empty($remoteDbDatasources)){
          foreach($remoteDbDatasources as $remoteDbDatasource){
            $dbDatasources[$remoteDbDatasource->type.'-'.$remoteDbDatasource->id]=$remoteDbDatasource;
          }
        }
      }
    }
    #endregion připravení seznamu externích zdrojů
    #region zpracovani seznamu datovych zdroju
    //načteme seznam lokálních datových zdrojů
    $datasources=$this->findDatasourcesByUser($user);

    //porovnáme oba seznamy
    $updatedDatasourcesIds=[];
    $updatedDbDatasourcesIds=[];
    if (!empty($datasources)&&!empty($dbDatasources)){
      //zkusíme najít příslušné překrývající se datové zdroje
      foreach($datasources as $datasource){
        foreach($dbDatasources as $dbDatasource){
          if ($datasource->dbDatasourceId==$dbDatasource->id){
            if ($datasource->name!=$dbDatasource->name){
              //aktualizace názvu datového zdroje (došlo k jeho přejmenování) a uložení
              $datasource->name=$dbDatasource->name;
              $this->datasourcesRepository->persist($datasource);
            }
            $updatedDatasourcesIds[]=$datasource->dbDatasourceId;
            $updatedDbDatasourcesIds[]=$dbDatasource->id;
            continue;
          }
        }
      }
    }
    if (!empty($datasources)){
      foreach($datasources as $datasource){
        if($datasource->available && !in_array($datasource->dbDatasourceId,$updatedDatasourcesIds)){
          //TODO výhledově podporovat datové zdroje na různých URL (aktuálně je přístup jen k výchozí vzdálené DB)
          //označení datového zdroje, který již není dostupný
          $datasource->available=false;
          $this->datasourcesRepository->persist($datasource);
        }
      }
    }
    if (!empty($dbDatasources)){
      $defaultDbConnections=[];
      foreach($dbDatasources as $dbDatasource){
        if (!in_array($dbDatasource->id,$updatedDbDatasourcesIds)){
          //vyřešení výchozího připojení k DB
          if (!isset($defaultDbConnections[$dbDatasource->type])){
            $defaultDbConnections[$dbDatasource->type]=$this->databaseFactory->getDefaultDbConnection($dbDatasource->type,$user);
          }
          //přidání nového datového zdroje...
          $datasource=Datasource::newFromDbConnection($defaultDbConnections[$dbDatasource->type]);
          $datasource->user=$user;
          $datasource->name=$dbDatasource->name;
          $datasource->dbDatasourceId=$dbDatasource->id;
          $datasource->type=$dbDatasource->type;
          $datasource->available=true;
          $this->datasourcesRepository->persist($datasource);
        }
      }
    }
    #endregion zpracovani seznamu datovych zdroju
  }

  /**
   * Funkce pro získání seznamu dostupných datových zdrojů
   *
   * @param User $user
   * @param bool $onlyAvailable=false
   * @return Datasource[]|null
   */
  public function findDatasourcesByUser(User $user, $onlyAvailable=false) {
    $selectParams=['user_id' => $user->userId];
    if($onlyAvailable){
      $selectParams['available']=true;
    }
    return $this->datasourcesRepository->findAllBy($selectParams);
  }


  /**
   * @param DatabaseFactory $databaseFactory
   * @param DatasourcesRepository $datasourcesRepository
   * @param DatasourceColumnsRepository $datasourceColumnsRepository
   */
  public function __construct(DatabaseFactory $databaseFactory, DatasourcesRepository $datasourcesRepository, DatasourceColumnsRepository $datasourceColumnsRepository) {
    $this->databaseFactory = $databaseFactory;
    $this->datasourcesRepository = $datasourcesRepository;
    $this->datasourceColumnsRepository = $datasourceColumnsRepository;
  }

  /**
   * Funkce pro nalezení datového zdroje dle zadaného ID
   * @param int $id
   * @return Datasource
   */
  public function findDatasource($id) {
    return $this->datasourcesRepository->find($id);
  }

  /**
   * Funkce pro nalezení datového zdroje s kontrolou oprávnění přístupu
   * @param int $id
   * @param User $user
   * @return Datasource
   * @throws EntityNotFoundException
   */
  public function findDatasourceWithCheckAccess($id, User $user) {
    $datasource=$this->findDatasource($id);
    if ($datasource->user->userId==$user->userId){
      return $datasource;
    }else{
      throw new EntityNotFoundException('Requested datasource was not found!');
    }
  }

  /**
   * Funkce pro aktualizaci info o datových sloupcích v DB
   * @param Datasource &$datasource
   * @param User $user
   */
  public function updateDatasourceColumns(Datasource &$datasource, User $user) {
    $database=$this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(), $user);
    $dbDatasource=$database->getDbDatasource($datasource->dbDatasourceId?$datasource->dbDatasourceId:$datasource->name);
    $datasource->size=$dbDatasource->size;
    $dbFields=$database->getDbFields($dbDatasource);

    #region připravení seznamu aktuálně existujících datasourceColumns
    /** @var DatasourceColumn[] $existingDatasourceColumnsByDbDatasourceFieldId */
    $existingDatasourceColumnsByDbDatasourceFieldId=[];
    /** @var DatasourceColumn[] $existingDatasourceColumnsByName */
    $existingDatasourceColumnsByName=[];
    /** @var DatasourceColumn[] $datasourceColumns */
    $datasourceColumns=$datasource->datasourceColumns;
    if (!empty($datasourceColumns)){
      foreach ($datasourceColumns as &$datasourceColumn){
        if (!empty($datasourceColumn->dbDatasourceFieldId)){
          $existingDatasourceColumnsByDbDatasourceFieldId[$datasourceColumn->dbDatasourceFieldId]=$datasourceColumn;
        }else{
          $existingDatasourceColumnsByName[$datasourceColumn->name]=$datasourceColumn;
        }

      }
    }
    #endregion

    #region aktualizace seznamu sloupců získaných z DB
    if (!empty($dbFields)){
      foreach($dbFields as $dbField){
        if (!empty($dbField->id) && is_int($dbField->id) && isset($existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id])){
          //sloupec s daným ID již je v databázi
          $datasourceColumn=$existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id];
          $datasourceColumn->name=$dbField->name;
          $datasourceColumn->type=$dbField->type;
          $datasourceColumn->active=true;
          if ($datasourceColumn->isModified()){
            $this->datasourceColumnsRepository->persist($datasourceColumn);
          }
          unset($existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id]);
        }elseif(!empty($dbField->name) && isset($existingDatasourceColumnsByName[$dbField->name])){
          //sloupec najdeme podle jména
          $datasourceColumn=$existingDatasourceColumnsByName[$dbField->name];
          $datasourceColumn->type=$dbField->type;
          $datasourceColumn->active=true;
          if ($datasourceColumn->isModified()){
            $this->datasourceColumnsRepository->persist($datasourceColumn);
          }
          unset($existingDatasourceColumnsByName[$dbField->name]);
        }else{
          //máme tu nový datový sloupec
          $datasourceColumn=new DatasourceColumn();
          $datasourceColumn->datasource=$datasource;
          $datasourceColumn->name=$dbField->name;
          if (is_int($dbField->id)){
            $datasourceColumn->dbDatasourceFieldId=$dbField->id;
          }
          $datasourceColumn->active=true;
          $datasourceColumn->name=$dbField->name;
          $datasourceColumn->type=$dbField->type;
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    #endregion
    #region deaktivace již neexistujících sloupců
    if (!empty($existingDatasourceColumnsByDbDatasourceFieldId)){
      foreach($existingDatasourceColumnsByDbDatasourceFieldId as &$datasourceColumn){
        $datasourceColumn->active=false;
        if ($datasourceColumn->isModified()){
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    if (!empty($existingDatasourceColumnsByName)){
      foreach($existingDatasourceColumnsByName as &$datasourceColumn){
        $datasourceColumn->active=false;
        if ($datasourceColumn->isModified()){
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    #endregion
    //aktualizace datového zdroje z DB
    $datasource=$this->findDatasource($datasource->datasourceId);
  }

  /**
   * Funkce pro nalezení DatasourceColumn dle jeho ID
   * @param Datasource|int $datasource
   * @param int $columnId
   * @return DatasourceColumn
   */
  public function findDatasourceColumn($datasource,$columnId) {
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->datasourceColumnsRepository->findBy(['datasource_id'=>$datasource,'datasource_column_id'=>$columnId]);
  }

  /**
   * Funkce pro nalezení DatasourceColumn podle jména
   * @param Datasource|int $datasource
   * @param string $columnName
   * @return DatasourceColumn
   */
  public function findDatasourceColumnByName($datasource, $columnName) {
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->datasourceColumnsRepository->findBy(['datasource_id'=>$datasource,'name'=>$columnName]);
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
  public function prepareNewDatasourceForUser($dbType, User $user,$ignoreCheck=false){
    $defaultDbConnection=$this->databaseFactory->getDefaultDbConnection($dbType, $user);
    $datasource = Datasource::newFromDbConnection($defaultDbConnection);
    if ($dbType==DbConnection::TYPE_UNLIMITED){return $datasource;}

    if ($ignoreCheck){//FIXME
      return $datasource;
    }

    //FIXME aktualizovat pro kompatibilitu s datovou službou
    /*TODO tato kontrola by neměla být prováděna při každém requestu...
    try{
      $database=$this->databaseFactory->getDatabaseInstance($defaultDbConnection,$user);
      $this->databasesFacade->openDatabase($defaultDbConnection);
    }catch (\Exception $e){
      //pokud došlo k chybě, pokusíme se vygenerovat uživatelský účet a databázi
      $this->databasesFacade->openDatabase($this->getAdminDbConnection($dbType));
      if (!$this->databasesFacade->createUserDatabase($defaultDbConnection)){
        throw new \Exception('Database creation failed!');
      }
    }*/
    return $datasource;
  }

  /**
   * Funkce pro export pole s informacemi z DataDictionary
   * @param Datasource $datasource
   * @param User $user
   * @param int &rowsCount = null - počet řádků v datasource
   * @return array
   */
  public function exportDataDictionaryArr(Datasource $datasource, User $user, &$rowsCount = null) {
    $output = [];
    $this->updateDatasourceColumns($datasource, $user);//aktualizace seznamu datových sloupců
    foreach($datasource->datasourceColumns as $datasourceColumn){
      if (!$datasourceColumn->active){continue;}
      $output[$datasourceColumn->name]=$datasourceColumn->type;
    }
    $rowsCount = $datasource->size;
    return $output;
  }

  /**
   * Funkce pro kontrolu, jestli je daný uživatel oprávněn přistupovat ke zvolenému mineru
   * @param Datasource|int $datasource
   * @param User|int $user
   * @return bool
   */
  public function checkDatasourceAccess($datasource, $user) {
    if (!($datasource instanceof Datasource)){
      $datasource=$this->findDatasource($datasource);
    }
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $datasource->user->userId==$user;
  }


#  /**
#   * Funkce vracející admin přístupy k DB daného typu
#   * @param string $dbType
#   * @return DbConnection
#   */
#  public function getAdminDbConnection($dbType){
#  $dbConnection=new DbConnection();
#  $databaseConfig=$this->databasesConfig[$dbType];
#  $dbConnection->type=$dbType;
#  $dbConnection->dbUsername=(!empty($databaseConfig['data_username'])?$databaseConfig['data_username']:@$databaseConfig['username']);
#  $dbConnection->dbPassword=(!empty($databaseConfig['data_password'])?$databaseConfig['data_password']:@$databaseConfig['password']);
#  $dbConnection->dbServer=(!empty($databaseConfig['data_server'])?$databaseConfig['data_server']:@$databaseConfig['server']);
#  if (!empty($databaseConfig['data_port'])){
#  $dbConnection->dbPort=$databaseConfig['data_port'];
#  }
#  return $dbConnection;
#  }
#
#
#  /**
#   * Funkce pro kontrolu, jestli jsou všechny sloupce z daného datového zdroje namapované na formáty z knowledge base
#   * @param Datasource|int $datasource
#   * @param bool $reloadColumns = false
#   * @return bool
#   */
#  public function checkDatasourceColumnsFormatsMappings($datasource, $reloadColumns = false){
#  if ($datasource->isDetached()){
#  exit('xxx');//FIXME
#  }
#  if (!($datasource instanceof Datasource)){
#  $datasource=$this->findDatasource($datasource);
#  }
#
#  if ($reloadColumns){
#  $this->reloadDatasourceColumns($datasource);
#  }
#
#  $datasourceColumns=$datasource->datasourceColumns;
#  foreach ($datasourceColumns as &$datasourceColumn){
#  if (empty($datasourceColumn->format)){
#  return false;
#  }
#  }
#  return true;
#  }
#
#  /**
#   * @param Datasource $datasource
#   * @param bool $reloadColumns = true - true, pokud má být zaktualizován seznam
#   * @return bool
#   */
#  public function saveDatasource(Datasource &$datasource, $reloadColumns = true) {
#  $result = $this->datasourcesRepository->persist($datasource);
#  if ($reloadColumns) {
#  ///XXX $this->reloadDatasourceColumns($datasource);
#  }
#  return $result;
#  }
#
#
#  /**
#   * Funkce pro uložení entity DatasourceColumn
#   * @param DatasourceColumn $datasourceColumn
#   * @return int|bool
#   */
#  public function saveDatasourceColumn(DatasourceColumn &$datasourceColumn){
#  $result = $this->datasourceColumnsRepository->persist($datasourceColumn);
#  return $result;
#  }
#
#  /**
#   * @param Datasource|int $datasource
#   * @return int
#   */
#  public function deleteDatasource($datasource){
#  if (!($datasource instanceof Datasource)){
#  $datasource=$this->datasourcesRepository->find($datasource);
#  }
#  return $this->datasourcesRepository->delete($datasource);
#  }
}