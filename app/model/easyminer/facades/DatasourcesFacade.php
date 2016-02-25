<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourceColumnsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourcesRepository;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

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
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var array $databasesConfig - konfigurace jednotlivých připojení k DB */
  private $databasesConfig;

  private static $dbTypesWithRemoteDatasources=['limited','unlimited'];

  #region ///REVIDOVANÉ METODY///////////////////////////////////////////////////////////////////////////////////////////////

  /**
   * Funkce pro aktualizaci informací o vzdálených datových zdrojích
   *
   * @param User $user
   */
  public function updateRemoteDatasourcesByUser(User $user){
    $supportedDbTypes = $this->databaseFactory->getDbTypes();

    foreach(self::$dbTypesWithRemoteDatasources as $dbType){
      if (in_array($dbType,$supportedDbTypes)){
        //načteme datové zdroje ze vzdálené databáze
        $database = $this->databaseFactory->getDatabaseInstanceWithDefaultDbConnection($dbType, $user);
        $dbDatasources = $database->getDbDatasources();

        //načteme seznam lokálních datových zdrojů
        $datasources=$this->findDatasourcesByUser($user);

        //porovnáme oba seznamy
        $updatedDatasourcesIds=[];
        $updatedDbDatasourcesIds=[];
        if (!empty($datasources)&&!empty($dbDatasources)){
          //zkusíme najít příslušné překrývající se datové zdroje
          foreach($datasources as $datasource){
            foreach($dbDatasources as $dbDatasource){
              if ($datasource->remoteId==$dbDatasource->remoteId){
                if ($datasource->name!=$dbDatasource->name){
                  //aktualizace názvu datového zdroje (došlo k jeho přejmenování) a uložení
                  $datasource->name=$dbDatasource->name;
                  $this->datasourcesRepository->persist($datasource);
                }
                $updatedDatasourcesIds[]=$datasource->remoteId;
                $updatedDbDatasourcesIds[]=$dbDatasource->remoteId;
                continue;
              }
            }
          }
        }
        if (!empty($datasources)){
          foreach($datasources as $datasource){
            if($datasource->available && !in_array($datasource->remoteId,$updatedDatasourcesIds)){
              //TODO výhledově podporovat datové zdroje na různých URL (aktuálně je přístup jen k výchozí vzdálené DB)
              //označení datového zdroje, který již není dostupný
              $datasource->available=false;
              $this->datasourcesRepository->persist($datasource);
            }
          }
        }
        if (!empty($dbDatasources)){
          foreach($dbDatasources as $dbDatasource){
            if (!in_array($dbDatasource->remoteId,$updatedDbDatasourcesIds)){
              //přidání nového datového zdroje...
              $datasource=new Datasource();
              $datasource->user=$user;
              $datasource->name=$dbDatasource->name;
              $datasource->remoteId=$dbDatasource->remoteId;
              $datasource->type=$dbDatasource->type;
              $datasource->available=true;
              $this->datasourcesRepository->persist($datasource);
            }
          }
        }
      }
    }
    $this->datasourcesRepository->findAllBy(['user_id'=>$user]);
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

  #endregion ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


  /**
   * @param int $id
   * @return Datasource
   */
  public function findDatasource($id) {
    return $this->datasourcesRepository->find($id);
  }

  /**
   * @param Datasource|int $datasource
   * @param int $column
   * @return DatasourceColumn
   */
  public function findDatasourceColumn($datasource,$column) {
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->datasourceColumnsRepository->findBy(array('datasource_id'=>$datasource,'datasource_column_id'=>$column));
  }

  /**
   * @param Datasource|int $datasource
   * @param string $columnName
   * @return DatasourceColumn
   */
  public function findDatasourceColumnByName($datasource,$columnName) {
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->datasourceColumnsRepository->findBy(array('datasource_id'=>$datasource,'name'=>$columnName));
  }

  /**
   * Funkce pro kontrolu, jestli jsou všechny sloupce z daného datového zdroje namapované na formáty z knowledge base
   * @param Datasource|int $datasource
   * @param bool $reloadColumns = false
   * @return bool
   */
  public function checkDatasourceColumnsFormatsMappings($datasource, $reloadColumns = false){
    if ($datasource->isDetached()){
      exit('xxx');//FIXME
    }
    if (!($datasource instanceof Datasource)){
      $datasource=$this->findDatasource($datasource);
    }

    if ($reloadColumns){
      $this->reloadDatasourceColumns($datasource);
    }

    $datasourceColumns=$datasource->datasourceColumns;
    foreach ($datasourceColumns as &$datasourceColumn){
      if (empty($datasourceColumn->format)){
        return false;
      }
    }
    return true;
  }

  /**
   * @param Datasource $datasource
   * @param bool $reloadColumns = true - true, pokud má být zaktualizován seznam
   * @return bool
   */
  public function saveDatasource(Datasource &$datasource, $reloadColumns = true) {
    $result = $this->datasourcesRepository->persist($datasource);
    if ($reloadColumns) {
      $this->reloadDatasourceColumns($datasource);
    }
    return $result;
  }


  /**
   * Funkce pro uložení entity DatasourceColumn
   * @param DatasourceColumn $datasourceColumn
   * @return int|bool
   */
  public function saveDatasourceColumn(DatasourceColumn &$datasourceColumn){
    $result = $this->datasourceColumnsRepository->persist($datasourceColumn);
    return $result;
  }

  /**
   * Funkce pro aktualizaci info o sloupcích v daném datovém zdroji
   * @param Datasource $datasource
   * @throws \LeanMapper\Exception\InvalidStateException
   * @throws \Nette\Application\ApplicationException
   */
  public function reloadDatasourceColumns(Datasource &$datasource){
    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    $datasourceColumns=$datasource->datasourceColumns;
    $datasourceColumnsArr=array();
    if (!empty($datasourceColumns)){
      foreach ($datasourceColumns as $datasourceColumn){
        $datasourceColumnsArr[$datasourceColumn->name]=$datasourceColumn;
      }
    }

    /** @var DbColumn[] $dbColumns */
    $dbColumns = $this->databasesFacade->getColumns($datasource->dbTable);

    if (!empty($dbColumns)) {
      foreach ($dbColumns as $dbColumn) {
        if ($dbColumn->name=='id'){continue;/*ignorujeme sloupec s ID*/}
        if (isset($datasourceColumnsArr[$dbColumn->name])) {
          unset($datasourceColumnsArr[$dbColumn->name]);
        } else {
          //vytvoříme info o datovém sloupci
          $datasourceColumn = new DatasourceColumn();
          $datasourceColumn->name = $dbColumn->name;
          $datasourceColumn->datasource = $datasource;
          switch ($dbColumn->dataType){
            case DbColumn::TYPE_FLOAT: $datasourceColumn->type=DatasourceColumn::TYPE_FLOAT;break;
            case DbColumn::TYPE_INTEGER: $datasourceColumn->type=DatasourceColumn::TYPE_INTEGER;break;
            default: $datasourceColumn->type=DatasourceColumn::TYPE_STRING;
          }

          $datasourceColumn->strLen=$dbColumn->strLength;

          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    if (!empty($datasourceColumnsArr)) {
      foreach ($datasourceColumnsArr as $datasourceColumn) {
        //odmažeme info o sloupcích, které v datové tabulce již neexistují
        $this->datasourceColumnsRepository->delete($datasourceColumn);
      }
    }

    $datasource=$this->findDatasource($datasource->datasourceId);
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
  public function prepareNewDatasourceForUser($dbType, User $user,$ignoreCheck=false){
    $defaultDbConnection=$this->databaseFactory->getDefaultDbConnection($dbType, $user);
    $datasource = Datasource::newFromDbConnection($defaultDbConnection);
    if ($dbType==DatabasesFacade::DB_TYPE_DBS_UNLIMITED){return $datasource;}

    if ($ignoreCheck){//FIXME
      return $datasource;
    }

    //FIXME aktualizovat pro kompatibilitu s datovou službou
    //TODO tato kontrola by neměla být prováděna při každém requestu...
    try{
      $database=$this->databaseFactory->getDatabaseInstance($defaultDbConnection,$user);
      $this->databasesFacade->openDatabase($defaultDbConnection);
    }catch (\Exception $e){
      //pokud došlo k chybě, pokusíme se vygenerovat uživatelský účet a databázi
      $this->databasesFacade->openDatabase($this->getAdminDbConnection($dbType));
      if (!$this->databasesFacade->createUserDatabase($defaultDbConnection)){
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
    //TODO remove...
    return Strings::substring($user->getDbPassword(),2,3).Strings::substring(sha1($user->userId.$user->getDbPassword()),4,5);
  }

  /**
   * Funkce vracející admin přístupy k DB daného typu
   * @param string $dbType
   * @return DbConnection
   */
  public function getAdminDbConnection($dbType){
    $dbConnection=new DbConnection();
    $databaseConfig=$this->databasesConfig[$dbType];
    $dbConnection->type=$dbType;
    $dbConnection->dbUsername=(!empty($databaseConfig['data_username'])?$databaseConfig['data_username']:@$databaseConfig['username']);
    $dbConnection->dbPassword=(!empty($databaseConfig['data_password'])?$databaseConfig['data_password']:@$databaseConfig['password']);
    $dbConnection->dbServer=(!empty($databaseConfig['data_server'])?$databaseConfig['data_server']:@$databaseConfig['server']);
    if (!empty($databaseConfig['data_port'])){
      $dbConnection->dbPort=$databaseConfig['data_port'];
    }
    return $dbConnection;
  }

  /**
   * Funkce pro odstranění datového sloupce z databáze
   * @param Datasource|int $datasource
   * @param DatasourceColumn|int $column
   * @return bool
   */
  public function deleteDatasourceColumn($datasource, $column){
    if (!$datasource instanceof Datasource){
      $datasource=$this->findDatasource($datasource);
    }
    if (!($column instanceof DatasourceColumn)){
      $column=$this->findDatasourceColumn($datasource,$column);
    }
    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    if ($this->databasesFacade->deleteColumn($datasource->dbTable,$column->name)){
      $this->datasourceColumnsRepository->delete($column);
      $this->reloadDatasourceColumns($datasource);
      return true;
    }else{
      $this->reloadDatasourceColumns($datasource);
      return false;
    }
  }

  /**
   * Funkce pro přejmenování datového sloupce v databázi
   * @param Datasource|int $datasource
   * @param DatasourceColumn|int $column
   * @param string $newName
   * @return bool
   */
  public function renameDatasourceColumn($datasource, $column, $newName){
    if (!($datasource instanceof Datasource)){
      $datasource=$this->findDatasource($datasource);
    }
    if (!($column instanceof DatasourceColumn)){
      $column=$this->findDatasourceColumn($datasource,$column);
    }
    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    if ($this->databasesFacade->renameColumn($datasource->dbTable,$column->name,$newName)){
      $column->name=$newName;
      $this->saveDatasourceColumn($column);
      $this->reloadDatasourceColumns($datasource);
      return true;
    }else{
      $this->reloadDatasourceColumns($datasource);
      return false;
    }
  }

  /**
   * Funkce pro export pole s informacemi z DataDictionary a TransformationDictionary
   * @param Datasource $datasource
   * @param Metasource|null $metasource
   * @return array
   */
  public function exportDictionariesArr(Datasource $datasource,Metasource $metasource=null) {
    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    $output=array('dataDictionary'=>array(),'transformationDictionary'=>array(),'recordCount'=>$this->databasesFacade->getRowsCount($datasource->dbTable));

    #region datafields
    foreach($datasource->datasourceColumns as $datasourceColumn){
      $output['dataDictionary'][$datasourceColumn->name]=($datasourceColumn->type==DatasourceColumn::TYPE_STRING?'string':'integer');//TODO kontrola, jaké má smysl vracet datové typy....
    }
    #endregion datafields

    #region atributy
    if (!empty($metasource) && !empty($metasource->attributes)) {
      $this->databasesFacade->openDatabase($metasource->getDbConnection());
      foreach($metasource->attributes as $attribute) {
        $valuesArr=array();
        try{
          $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute->name,true);
          if (!empty($valuesStatistics->valuesArr)){
            foreach ($valuesStatistics->valuesArr as $value=>$count){
              $valuesArr[]=$value;
            }
          }
        }catch (\Exception $e){}
        $output['transformationDictionary'][$attribute->name]=array('choices'=>$valuesArr);
      }
    }
    #endregion atributy

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
} 