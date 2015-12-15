<?php
namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;

/**
 * Class DatabasesPresenter
 *
 * @package EasyMinerCenter\RestModule\Presenters
 */
class DatabasesPresenter extends BaseResourcePresenter {
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  string $dbType */
  public $dbType;

  /**
   * Akce pro ověření přihlášeného uživatele
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
   *   )
   * )
   */
  public function actionRead($dbType) {
    $this->setXmlMapperElements('database');
    $dbType=strtolower($dbType);
    if ($dbType=='limited'||$dbType=='unlimited'){
      $dbType='dbs_'.$dbType;
    }
    //připravení informací o datovém zdroji pro konkrétního uživatele...
    $datasource=$this->datasourcesFacade->prepareNewDatasourceForUser($this->currentUser,$dbType);

    $arr=[
      'server'=>$datasource->dbServer
    ];
    if (!empty($datasource->dbPort)){
      $arr['port']=$datasource->dbPort;
    }
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


  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
}