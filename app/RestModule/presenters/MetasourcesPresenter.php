<?php

namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use Nette\Application\BadRequestException;

/**
 * Class MetasourcesPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MetasourcesPresenter extends BaseResourcePresenter{

  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;

  #region actionRead
  /**
   * @param int $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Metasources"},
   *   path="/metasources/{id}",
   *   summary="Get meta source basic details",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Metasource ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Metasource details",
   *     @SWG\Schema(
   *       ref="#/definitions/MetasourceWithAttributesResponse"
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested metasource was not found.")
   * )
   */
  public function actionRead($id) {
    $metasource=$this->findMetasourceWithCheckAccess($id);
    $this->metasourcesFacade->updateMetasourceAttributes($metasource, $this->getCurrentUser());
    $result=$metasource->getDataArr();
    if (!empty($metasource->attributes)){
      foreach($metasource->attributes as $attribute){
        $result['attribute'][]=['id'=>$attribute->attributeId,'name'=>$attribute->name,'type'=>$attribute->type,'datasourceColumnId'=>$attribute->datasourceColumn->datasourceColumnId,'preprocessingId'=>$attribute->preprocessing->preprocessingId,'uniqueValues'=>$attribute->uniqueValuesCount,'active'=>$attribute->active];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }
  #endregion actionRead

  /**
   * Private method for finding a concrete metasource with check of user privileges
   * @param int $datasourceId
   * @throws BadRequestException
   * @return Metasource
   */
  private function findMetasourceWithCheckAccess($metasourceId) {
    try{
      $metasource=$this->metasourcesFacade->findMetasource($metasourceId);
      if (!$this->metasourcesFacade->checkMetasourceAccess($metasource,$this->getCurrentUser())){
        throw new BadRequestException("You are not authorized to use the selected datasource!");
      }
    }catch (\Exception $e){
      throw new BadRequestException("Requested datasource was not found or is not accessible!");
    }
    return $metasource;
  }



  #region injections
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade) {
    $this->metasourcesFacade=$metasourcesFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="MetasourceBasicResponse",
 *   title="MetasourceBasicInfo",
 *   required={"id","type","name","available"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the metasource"),
 *   @SWG\Property(property="type",type="string",description="Type of the used database",enum={"limited","unlimited","mysql"}),
 *   @SWG\Property(property="name",type="string",description="Name of the database table"),
 *   @SWG\Property(property="dbDatasourceId",type="integer",description="ID of the metasource on the remote data service"),
 *   @SWG\Property(property="available",type="boolean"),
 * )
 * @SWG\Definition(
 *   definition="MetasourceWithAttributesResponse",
 *   title="MetasourceBasicInfo",
 *   required={"id","type","dbServer","dbUsername","dbName","dbTable"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the metasource"),
 *   @SWG\Property(property="type",type="string",description="Type of the used database"),
 *   @SWG\Property(property="name",type="string",description="Name of the database table"),
 *   @SWG\Property(property="ppDatasetId",type="integer",description="ID of the metasource on the remote data service"),
 *   @SWG\Property(property="available",type="boolean"),
 *   @SWG\Property(property="attribute",type="array",
 *     @SWG\Items(ref="#/definitions/AttributeBasicInfoResponse")
 *   )
 * )
 * @SWG\Definition(
 *   definition="AttributeBasicInfoResponse",
 *   required={"id","name","type","datasourceColumnId","preprocessingId","uniqueValues","active"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the attribute"),
 *   @SWG\Property(property="name",type="string",description="Name of the attribute"),
 *   @SWG\Property(property="type",type="string",enum={"nominal","numeric"},description="Data type of the attribute"),
 *   @SWG\Property(property="datasourceColumnId",type="int"),
 *   @SWG\Property(property="preprocessingId",type="int"),
 *   @SWG\Property(property="uniqueValues",type="int",description="Count of unique values of the attribute"),
 *   @SWG\Property(property="active",type="bool")
 * )
 */