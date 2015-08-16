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
   *   path="/databases/mysql",
   *   summary="Get user access credentials for MySQL",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
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
    if ($dbType!='mysql'){
      throw new BadRequestException("Bad database type!");
    }
    $this->setXmlMapperElements('database');
    $datasource=$this->datasourcesFacade->prepareNewDatasourceForUser($this->currentUser,DatabasesFacade::DB_TYPE_MYSQL);
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