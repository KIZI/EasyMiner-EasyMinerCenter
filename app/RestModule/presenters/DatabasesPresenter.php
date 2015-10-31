<?php
namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use Nette\Application\BadRequestException;

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
   *   summary="Get user access credentials for MySQL",
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
      'server'=>$datasource->dbServer,
      'username'=>$datasource->dbUsername,
      'password'=>$datasource->getDbPassword(),
      'database'=>$datasource->dbName
    ];
    if (!empty($datasource->dbPort)){
      $arr['port']=$datasource->dbPort;
    }
    $this->resource=$arr;
    $this->sendResource();
  }


  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
}