<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;


use EasyMinerCenter\Model\Data\Entities\DbColumn;
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
  public function __construct($databasesConfig, DatasourcesRepository $datasourcesRepository, DatasourceColumnsRepository $datasourceColumnsRepository, DatabasesFacade $databasesFacade) {
    $this->datasourcesRepository = $datasourcesRepository;
    $this->datasourceColumnsRepository = $datasourceColumnsRepository;
    $this->databasesConfig = $databasesConfig;
    $this->databasesFacade = $databasesFacade;
  }

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
   * @param int|User $user
   * @return Datasource[]|null
   */
  public function findDatasourcesByUser($user) {
    if ($user instanceof User) {
      $user = $user->userId;
    }
    return $this->datasourcesRepository->findAllBy(array('user_id' => $user));
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
    return $datasource->user->userId=$user;
  }
} 