<?php
namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;

/**
 * Class DatabasesPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DatabasesPresenter extends BaseResourcePresenter {
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var  string $dbType */
  public $dbType;

  /**
   * Action returning database connection details for the current user
   * @param string $dbType
   * @SWG\Get(
   *   tags={"Databases"},
   *   path="/databases/{dbType}",
   *   summary="Get user access credentials for MySQL and other databases",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     name="dbType",
   *     description="Type of database",
   *     required=true,
   *     type="string",
   *     in="path",
   *     enum={"limited","unlimited","mysql"}
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Connection params",
   *     @SWG\Schema(
   *       required={"server","username","password","database"},
   *       @SWG\Property(property="server",type="string",description="DB Server (IP or URL)"),
   *       @SWG\Property(property="port",type="integer",description="DB server port (empty if default)"),
   *       @SWG\Property(property="username",type="string",description="Database username"),
   *       @SWG\Property(property="password",type="string",description="Database password"),
   *       @SWG\Property(property="database",type="string",description="Database name"),
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(
   *     response=404,
   *     description="Database not available"
   *   )
   * )
   */
  public function actionRead($dbType) {
    $this->setXmlMapperElements('database');
    $dbType=strtolower($dbType);
    //check, when was realized the last check of DB connection (availability)
    if ($this->currentUser->getLastDbCheck($dbType)<time()-DatabaseFactory::DB_AVAILABILITY_CHECK_INTERVAL){
      if (!in_array($dbType, $this->databaseFactory->getDbTypes())){
        $this->error('Database is not configured: '.$dbType);
      }

      $this->currentUser->setLastDbCheck($dbType,time());
      $this->usersFacade->saveUser($this->currentUser);//TODO tahle kontrola by ještě měla být optimalizovaná
      $dbAvailabilityCheck=true;
    }else{
      $dbAvailabilityCheck=false;
    }

    //prepare info about the datasource for the current User
    $datasource=$this->datasourcesFacade->prepareNewDatasourceForUser($dbType, $this->currentUser,!$dbAvailabilityCheck);

    $dbConnection=$datasource->getDbConnection();
    #region prepare result array
    $result=[];
    if (!empty($dbConnection->dbServer)){
      $result['server']=$dbConnection->dbServer;
    }
    if (!empty($dbConnection->dbApi)){
      $result['api']=$dbConnection->dbApi;
    }
    if (!empty($dbConnection->dbPort)){
      $result['port']=$dbConnection->dbPort;
    }
    #endregion prepare result array

    $arr=&$result;
    if (!empty($datasource->dbUsername)){
      $arr['username']=$datasource->dbUsername;
    }
    $dbPassword=$datasource->getDbPassword();
    if (!empty($dbPassword)){
      $arr['password']=$dbPassword;
    }
    if (!empty($datasource->dbName)){
      $arr['database']=$datasource->dbName;
    }

    $this->resource=$arr;
    $this->sendResource();
  }


  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * @param DatabaseFactory $databaseFactory
   */
  public function injectDatabaseFactory(DatabaseFactory $databaseFactory) {
    $this->databaseFactory=$databaseFactory;
  }
  #endregion injections
}