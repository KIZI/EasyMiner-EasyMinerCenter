<?php

namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use Nette\Application\BadRequestException;

/**
 * Class DatasourcesPresenter - presenter pro práci s datovými zdroji
 * @package EasyMinerCenter\RestModule\Presenters
 */
class DatasourcesPresenter extends BaseResourcePresenter{

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;

  /**
   * Akce pro import CSV souboru (případně komprimovaného v ZIP archívu)
   * @SWG\Post(
   *   tags={"Datasources"},
   *   path="/datasources",
   *   summary="Create new datasource using uploaded file",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Response(
   *     response=200,
   *     description="Successfully authenticated",
   *     @SWG\Schema(
   *       required={"id","name"},
   *       @SWG\Property(property="id",type="integer",description="Authenticated user ID"),
   *       @SWG\Property(property="name",type="string",description="Authenticated user name"),
   *       @SWG\Property(property="email",type="string",description="Authenticated user e-mail"),
   *       @SWG\Property(
   *         property="role",
   *         type="array",
   *         description="Authenticated user roles",
   *         @SWG\Items(type="string")
   *       ),
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   )
   * )
   */
  public function actionCreate() {
    //TODO implementovat import CSV jako nový datasource




  }


  /**
   * @param int|null $id=null
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Datasources"},
   *   path="/datasources/{id}",
   *   summary="Get data source basic details",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Datasource ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Datasource basic details",
   *     @SWG\Schema(
   *       ref="#/definitions/DatasourceWithColumnsResponse"
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested datasource was not found.")
   * )
   */
  public function actionRead($id=null) {
    if ($id==null){
      $this->forward('list');return;
    }
    $datasource=$this->findDatasourceWithCheckAccess($id);
    $result=$datasource->getDataArr();
    if (!empty($datasource->datasourceColumns)){
      foreach($datasource->datasourceColumns as $column){
        $result['column'][]=['name'=>$column->name,'type'=>$column->type];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }

  /**
   * Akce vracející seznam datových zdrojů pro aktuálního uživatele
   * @SWG\Get(
   *   tags={"Datasources"},
   *   path="/datasources",
   *   summary="Get list of datasources for the current user",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Response(
   *     response="200",
   *     description="List of datasources",
   *     @SWG\Schema(
   *       type="array",
   *       @SWG\Items(
   *         ref="#/definitions/DatasourceBasicResponse"
   *       )
   *     )
   *   )
   * )
   */
  public function actionList() {
    $this->setXmlMapperElements('datasources','datasource');
    $currentUser=$this->getCurrentUser();
    $datasources=$this->datasourcesFacade->findDatasourcesByUser($currentUser);
    $result=[];
    if (!empty($datasources)){
      foreach ($datasources as $datasource){
        $result[]=$datasource->getDataArr();
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }


  /**
   * Funkce pro nalezení datového zdroje s kontrolou oprávnění přístupu
   * @param int $datasourceId
   * @throws BadRequestException
   * @return Datasource
   */
  private function findDatasourceWithCheckAccess($datasourceId) {
    try{
      $datasource=$this->datasourcesFacade->findDatasource($datasourceId);
      if (!$this->datasourcesFacade->checkDatasourceAccess($datasource,$this->getCurrentUser())){
        throw new BadRequestException("You are not authorized to use the selected datasource!");
      }
    }catch (\Exception $e){
      throw new BadRequestException("Requested datasource was not found or is not accessible!");
    }
    return $datasource;
  }



  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="DatasourceBasicResponse",
 *   title="DatasourceBasicInfo",
 *   required={"id","type","dbServer","dbUsername","dbName","dbTable"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the datasource"),
 *   @SWG\Property(property="type",type="string",description="Type of the used database"),
 *   @SWG\Property(property="dbServer",type="string",description="Database server"),
 *   @SWG\Property(property="dbPort",type="integer",description="Database port"),
 *   @SWG\Property(property="dbUsername",type="string",description="Database user name"),
 *   @SWG\Property(property="dbName",type="string",description="Name of the database"),
 *   @SWG\Property(property="dbTable",type="string",description="Name of the database table"),
 * )
 * @SWG\Definition(
 *   definition="DatasourceWithColumnsResponse",
 *   title="DatasourceBasicInfo",
 *   required={"id","type","dbServer","dbUsername","dbName","dbTable"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the datasource"),
 *   @SWG\Property(property="type",type="string",description="Type of the used database"),
 *   @SWG\Property(property="dbServer",type="string",description="Database server"),
 *   @SWG\Property(property="dbPort",type="integer",description="Database port"),
 *   @SWG\Property(property="dbUsername",type="string",description="Database user name"),
 *   @SWG\Property(property="dbName",type="string",description="Name of the database"),
 *   @SWG\Property(property="dbTable",type="string",description="Name of the database table"),
 *   @SWG\Property(property="column",type="array",
 *     @SWG\Items(ref="#/definitions/ColumnBasicInfoResponse")
 *   )
 * )
 * @SWG\Definition(
 *   definition="ColumnBasicInfoResponse",
 *   required={"name"},
 *   @SWG\Property(property="name",type="string")
 * )
 */