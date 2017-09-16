<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Databases\DataService\LimitedDatabase;
use EasyMinerCenter\Model\Data\Databases\DataService\UnlimitedDatabase;
use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbValue;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourceColumnsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\DatasourcesRepository;
use Nette\NotImplementedException;

/**
 * Class DatasourcesFacade - method for work with datasources
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DatasourcesFacade {
  /** @var  DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  DatasourceColumnsRepository $datasourceColumnsRepository */
  private $datasourceColumnsRepository;
  /** @var string[] $dbTypesWithRemoteDatasources - list of types of databases with remote datasources */
  private static $dbTypesWithRemoteDatasources=[DbConnection::TYPE_LIMITED,DbConnection::TYPE_UNLIMITED];

  /**
   * Method returning list of active (configured) types of databases
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
   * Method returning preferred type of database
   * @return string
   */
  public function getPreferredDbType(){
    //TODO implementovat načtení výchozího typu databáze z konfigurace
    return DbConnection::TYPE_LIMITED;
  }

  /**
   * Method returning instance of database connection for concrete Datasource
   * @param Datasource $datasource
   * @return IDatabase
   */
  public function getDatasourceDatabase(Datasource $datasource){
    return $this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(), $datasource->user);
  }

  /**
   * Method returning configuration for direct access to services for work with databases (used in javascript upload in UI)
   * @return array
   */
  public function getDataServicesConfigByDbTypes(){
    $result = [];
    $dbTypes=$this->getDbTypes();
    if (!empty($dbTypes)){
      foreach($dbTypes as $dbType=>$value){
        //get URL for each database type
        $databaseConfig=$this->databaseFactory->getDatabaseConfig($dbType);
        $result[$dbType]=[
          'url'=>!empty($databaseConfig['apiExternalUrl'])?$databaseConfig['apiExternalUrl']:(!empty($databaseConfig['api'])?$databaseConfig['api']:'local'),
          'allowLongNames'=>isset($databaseConfig['allowLongNames'])?(bool)$databaseConfig['allowLongNames']:false,
          'supportedImportTypes'=>$databaseConfig['supportedImportTypes']
        ];
      }
    }
    return $result;
  }

  /**
   * Method returning true, if the selected database type supports long names of columns
   * @param string $dbType
   * @return bool
   */
  public function dbTypeSupportsLongNames($dbType){
    $databaseConfig=$this->databaseFactory->getDatabaseConfig($dbType);
    return isset($databaseConfig['allowLongNames'])?$databaseConfig['allowLongNames']:false;
  }

  /**
   * Method returning true, if the selected Datasource type supports long names of columns
   * @param Datasource $datasource
   * @return bool
   */
  public function datasourceSupportsLongNames(Datasource $datasource){
    return $this->dbTypeSupportsLongNames($datasource->type);
  }

  /**
   * Method returning array with list of databases by concrete types of importable data
   * @return array
   */
  public function getDbTypesByImportTypes(){
    $result=[];
    $dataServicesConfigByDbTypes=$this->getDataServicesConfigByDbTypes();
    if (!empty($dataServicesConfigByDbTypes)){
      foreach($dataServicesConfigByDbTypes as $dbType=>$dbTypeConfig){
        if (!empty($dbTypeConfig['supportedImportTypes'])){
          foreach($dbTypeConfig['supportedImportTypes'] as $importType){
            if (!isset($result[$importType])){
              $result[$importType]=[];
            }
            $result[$importType][]=$dbType;
          }
        }
      }
    }
    return $result;
  }


  /**
   * Method returning supported data types for the selected type of database
   * @param string $dbType
   * @return string[]
   */
  public function getSupportedImportTypesByDbType($dbType){
    $databaseConfig=$this->databaseFactory->getDatabaseConfig($dbType);
    return $databaseConfig['supportedImportTypes'];
  }

  /**
   * Method returning type of database, which supports unzip (if there is at least one database with this support)
   * @return null|string
   */
  public function getDatabaseUnzipServiceType(){
    $dbTypes=$this->getDbTypes();
    if (isset($dbTypes[LimitedDatabase::DB_TYPE])){
      return LimitedDatabase::DB_TYPE;
    }elseif(isset($dbTypes[UnlimitedDatabase::DB_TYPE])){
      return UnlimitedDatabase::DB_TYPE;
    }else{
      return null;
    }
  }

  /**
   * Method for unzipping of preview data
   * @param string $data
   * @param string $compression
   * @param User $user
   * @return string
   * @throws \Exception
   */
  public function unzipPreviewData($data, $compression, User $user){
    $dbType=$this->getDatabaseUnzipServiceType();
    if (!$dbType){
      throw new \Exception('No database with unzip support found.');
    }
    $database=$this->databaseFactory->getDatabaseInstanceWithDefaultDbConnection($dbType,$user);
    return $database->unzipData($data, $compression);
  }


  /**
   * Method for update of informations about remote datasources
   * @param User $user
   */
  public function updateRemoteDatasourcesByUser(User $user){
    $supportedDbTypes = $this->databaseFactory->getDbTypes();
    #region prepare list of remote datasources
    /** @var DbDatasource[] $dbDatasources */
    $dbDatasources=[];
    $updatableDbTypes=[];
    foreach(self::$dbTypesWithRemoteDatasources as $dbType){
      if (in_array($dbType,$supportedDbTypes)){
        //read datasources from remote database (using database driver)
        $database = $this->databaseFactory->getDatabaseInstanceWithDefaultDbConnection($dbType, $user);
        $remoteDbDatasources = $database->getDbDatasources();
        if (!empty($remoteDbDatasources)){
          foreach($remoteDbDatasources as $remoteDbDatasource){
            $dbDatasources[$remoteDbDatasource->type.'-'.$remoteDbDatasource->id]=$remoteDbDatasource;
          }
        }
        $updatableDbTypes[]=$dbType;
      }
    }
    #endregion datasources from remote database (using database driver)
    #region process the list of datasources
    //read list of local datasources
    $datasources=$this->findDatasourcesByUser($user);

    //compare both lists of datasources
    $updatedDatasourcesIds=[];
    $updatedDbDatasourcesIds=[];
    if (!empty($datasources)&&!empty($dbDatasources)){
      //try to find appropriate, overlapping datasources
      foreach($datasources as $key=>$datasource){
        if (!in_array($datasource->type,$updatableDbTypes)){
          unset($datasources[$key]);
          continue;//ignore datasources, which cannot be updated through this way
        }
        foreach($dbDatasources as $dbDatasource){
          if ($datasource->dbDatasourceId==$dbDatasource->id){
            if ($datasource->name!=$dbDatasource->name){
              //update name of datasource (it was renamed) and save it
              $datasource->name=$dbDatasource->name;
            }
            if (!$datasource->available){
              $datasource->available=true;
            }
            if ($datasource->isModified()){
              $this->saveDatasource($datasource);
            }
            $updatedDatasourcesIds[]=$datasource->dbDatasourceId;
            $updatedDbDatasourcesIds[]=$dbDatasource->id;
            unset($datasources[$key]);
            break;
          }
        }
      }
    }

    if (!empty($datasources)){
      foreach($datasources as $datasource){
        if($datasource->available && !in_array($datasource->dbDatasourceId,$updatedDatasourcesIds)){
          //mark datasource, which is not available any more
          $datasource->available=false;
          $this->saveDatasource($datasource);
        }
      }
    }

    if (!empty($dbDatasources)){
      $defaultDbConnections=[];
      foreach($dbDatasources as $dbDatasource){
        if (!in_array($dbDatasource->id,$updatedDbDatasourcesIds)){
          //solve the default database connection
          if (!isset($defaultDbConnections[$dbDatasource->type])){
            $defaultDbConnections[$dbDatasource->type]=$this->databaseFactory->getDefaultDbConnection($dbDatasource->type,$user);
          }
          //add new datasource
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
    #endregion process the list of datasources
  }

  /**
   * Method returning list of datasources for the given user
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
   * Method for creating new datasource based on DbDatasource, using the default config of database connection
   * @param DbDatasource $dbDatasource
   * @param User $user
   * @return Datasource
   */
  public function prepareNewDatasourceFromDbDatasource(DbDatasource $dbDatasource, User $user){
    $dbConnection=$this->databaseFactory->getDefaultDbConnection($dbDatasource->type,$user);
    $datasource=Datasource::newFromDbConnection($dbConnection);
    $datasource->name=$dbDatasource->name;
    $datasource->dbDatasourceId=$dbDatasource->id;
    $datasource->user=$user;
    $datasource->size=$dbDatasource->size;
    return $datasource;
  }

  /**
   * Method for checking of column names in the datasource (and renaming of them, if it is required)
   * @param Datasource $datasource
   * @param array $columnNames
   * @param User $user
   * @throws NotImplementedException
   */
  public function renameDatasourceColumns(Datasource $datasource, array $columnNames,User $user){
    $database=$this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(),$user);
    $dbDatasource=$database->getDbDatasource($datasource->dbDatasourceId);
    $dbFields=$database->getDbFields($dbDatasource);
    if (!empty($datasource->datasourceColumns)){
      throw new NotImplementedException('renameDatasourceColumns currently does not support existing datasources....');
    }

    if (!empty($dbFields)){
      $i=0;
      foreach($dbFields as $dbField){
        if ($dbField->name!=$columnNames[$i]){
          $database->renameDbField($dbField,$columnNames[$i]);
        }
        $i++;
      }
    }
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
   * Method for finding a datasource by the datasourceId
   * @param int $id
   * @return Datasource
   */
  public function findDatasource($id) {
    return $this->datasourcesRepository->find($id);
  }

  /**
   * Method for finding a datasource by the ID from remote database/data service
   * @param int $dbDatasourceFieldId
   * @return Datasource
   * @throws EntityNotFoundException
   */
  public function findDatasourceByDbDatasourceFieldId($dbDatasourceFieldId) {
    return $this->datasourcesRepository->findBy(['db_datasource_id'=>$dbDatasourceFieldId]);
  }
  
  /**
   * Method for finding a datasource by datasourceId, with check of user privileges
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
   * Method for updating the list of datasource columns
   * @param Datasource &$datasource
   * @param User $user
   */
  public function updateDatasourceColumns(Datasource &$datasource, User $user) {
    $database=$this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(), $user);
    $dbDatasource=$database->getDbDatasource($datasource->dbDatasourceId?$datasource->dbDatasourceId:$datasource->name);
    if ($datasource->size!=$dbDatasource->size){
      $datasource->size=$dbDatasource->size;
    }
    if ($dbDatasource->name!=$datasource->name){
      $datasource->name=$dbDatasource->name;
    }
    if ($datasource->isModified()){
      $this->saveDatasource($datasource);
    }
    $dbFields=$database->getDbFields($dbDatasource);

    #region prepare list of actually existing datasourceColumns
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
    #endregion prepare list of actually existing datasourceColumns

    #region update list of columns gained from DB
    if (!empty($dbFields)){
      foreach($dbFields as $dbField){
        if (!empty($dbField->id) && is_int($dbField->id) && isset($existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id])){
          //column with the given ID already exists in DB
          $datasourceColumn=$existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id];
          $modified=false;
          if ($datasourceColumn->name!=$dbField->name){
            $datasourceColumn->name=$dbField->name;
            $modified=true;
          }
          if ($datasourceColumn->type!=$dbField->type){
            $datasourceColumn->type=$dbField->type;
            $modified=true;
          }
          if (!$datasourceColumn->active){
            $datasourceColumn->active=true;
            $modified=true;
          }
          if ($datasourceColumn->uniqueValuesCount!=$dbField->uniqueValuesSize){
            $datasourceColumn->uniqueValuesCount=$dbField->uniqueValuesSize;
            $modified=true;
          }
          if ($modified){
            $this->datasourceColumnsRepository->persist($datasourceColumn);
          }
          unset($existingDatasourceColumnsByDbDatasourceFieldId[$dbField->id]);
        }elseif(!empty($dbField->name) && isset($existingDatasourceColumnsByName[$dbField->name])){
          //find column by name
          $datasourceColumn=$existingDatasourceColumnsByName[$dbField->name];
          $modified=false;
          if ($datasourceColumn->type!=$dbField->type){
            $datasourceColumn->type=$dbField->type;
            $modified=true;
          }
          if (!$datasourceColumn->active){
            $datasourceColumn->active=true;
            $modified=true;
          }
          if ($datasourceColumn->uniqueValuesCount!=$dbField->uniqueValuesSize){
            $datasourceColumn->uniqueValuesCount=$dbField->uniqueValuesSize;
            $modified=true;
          }
          if ($modified){
            $this->saveDatasourceColumn($datasourceColumn);
          }
          unset($existingDatasourceColumnsByName[$dbField->name]);
        }else{
          //we have there new column
          $datasourceColumn=new DatasourceColumn();
          $datasourceColumn->datasource=$datasource;
          $datasourceColumn->name=$dbField->name;
          $datasourceColumn->uniqueValuesCount=$dbField->uniqueValuesSize;
          if (is_int($dbField->id)){
            $datasourceColumn->dbDatasourceFieldId=$dbField->id;
          }
          $datasourceColumn->active=true;
          $datasourceColumn->type=$dbField->type;
          $this->saveDatasourceColumn($datasourceColumn);
        }
      }
    }
    #endregion update list of columns gained from DB

    #region deactivating of no more existing columns
    if (!empty($existingDatasourceColumnsByDbDatasourceFieldId)){
      foreach($existingDatasourceColumnsByDbDatasourceFieldId as &$datasourceColumn){
        if ($datasourceColumn->active){
          $datasourceColumn->active=false;
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    if (!empty($existingDatasourceColumnsByName)){
      foreach($existingDatasourceColumnsByName as &$datasourceColumn){
        if ($datasourceColumn->active){
          $datasourceColumn->active=false;
          $this->datasourceColumnsRepository->persist($datasourceColumn);
        }
      }
    }
    #endregion deactivating of no more existing columns

    //update datasource instace from DB
    $datasource=$this->findDatasource($datasource->datasourceId);
  }

  /**
   * Find DatasourceColumn by DatasourceId and DatasourceColumnId
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
   * Method for finding a DatasourceColumn by DatasourceId and name of the column
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
   * @param DatasourceColumn $datasourceColumn
   * @param int $offset = 0
   * @param int $limit = 1000
   * @return DbValue[]
   */
  public function getDatasourceColumnDbValues(DatasourceColumn $datasourceColumn,$offset=0,$limit=1000){
    $datasource=$datasourceColumn->datasource;

    $database=$this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(),$datasource->user);
    $dbDatasource=$database->getDbDatasource($datasourceColumn->datasource->dbDatasourceId);
    $dbField=$database->getDbField($dbDatasource,$datasourceColumn->dbDatasourceFieldId);
    return $database->getDbValues($dbField, $offset, $limit);
  }

  /**
   * Method for saving of a DatasourceColumn
   * @param DatasourceColumn $datasourceColumn
   * @return int|bool
   */
  public function saveDatasourceColumn(DatasourceColumn &$datasourceColumn){
    $result = $this->datasourceColumnsRepository->persist($datasourceColumn);
    return $result;
  }

  /**
   * Method for finding DatasourceColumn by ID of the column/field in remote database/data service
   * @param Datasource $datasource
   * @param int $dbDatasourceFieldId
   * @return DatasourceColumn
   * @throws EntityNotFoundException
   */
  public function findDatasourceColumnByDbDatasourceColumnId(Datasource $datasource, $dbDatasourceFieldId){
    return $this->datasourceColumnsRepository->findBy(['datasource_id'=>$datasource->datasourceId, $dbDatasourceFieldId]);
  }

  /**
   * Method for preparing of params for new datasource for the given user
   * @param string $dbType
   * @param User $user
   * @param bool $ignoreCheck
   * @return Datasource
   */
  public function prepareNewDatasourceForUser($dbType, User $user,$ignoreCheck=false){
    $defaultDbConnection=$this->databaseFactory->getDefaultDbConnection($dbType, $user);
    $datasource = Datasource::newFromDbConnection($defaultDbConnection);
    if ($dbType==DbConnection::TYPE_UNLIMITED){return $datasource;}


    if ($ignoreCheck){
      return $datasource;
    }
    
    $this->databaseFactory->checkDatabaseAvailability($defaultDbConnection,$user);

    return $datasource;
  }

  /**
   * Method for export of array with info from DataDictionary
   * @param Datasource $datasource
   * @param User $user
   * @param int &rowsCount = null - variable returning count of rows in DataSource
   * @return array
   */
  public function exportDataDictionaryArr(Datasource $datasource, User $user, &$rowsCount = null) {
    $output = [];
    $this->updateDatasourceColumns($datasource, $user);//aktualizace seznamu datových sloupců
    foreach($datasource->datasourceColumns as $datasourceColumn){
      if (!$datasourceColumn->active){continue;}
      $output[$datasourceColumn->datasourceColumnId]=[
        'name'=>$datasourceColumn->name,
        'type'=>$datasourceColumn->type
      ];
    }
    $rowsCount = $datasource->size;
    return $output;
  }

  /**
   * Method for checking, if the given user is allowed to access the selected datasource
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

  /**
   * Method for saving of Datasource
   * @param Datasource $datasource
   * @return int
   */
  public function saveDatasource(Datasource $datasource) {
    return $this->datasourcesRepository->persist($datasource);
  }

  /**
   * Method for deleting of concrete datasource
   * @param Datasource $datasource
   * @param MinersFacade $minersFacade
   * @return bool
   */
  public function deleteDatasource(Datasource $datasource, MinersFacade $minersFacade){
    //delete related data
    $this->deleteDatasourceData($datasource);
    //delete all attached miners
    $miners=$minersFacade->findMinersByDatasource($datasource);
    if (!empty($miners)){
      foreach($miners as $miner){
        $minersFacade->deleteMiner($miner);
      }
    }
    //delete the datasource
    return $this->datasourcesRepository->delete($datasource);
  }

  /**
   * Method for selecting remote datasource (with keeping info about it, keeping metasource and miners)
   * @param Datasource $datasource
   */
  public function deleteDatasourceData(Datasource $datasource){
    $database=$this->databaseFactory->getDatabaseInstance($datasource->getDbConnection(),$datasource->user);
    $dbDatasource = new DbDatasource($datasource->dbDatasourceId,$datasource->name,$datasource->type,$datasource->size);
    $database->deleteDbDatasource($dbDatasource);
    $datasource->dbDatasourceId=null;
    $datasource->available=false;
    $this->saveDatasource($datasource);
  }

}