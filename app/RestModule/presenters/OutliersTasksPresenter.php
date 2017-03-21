<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\NotImplementedException;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use Nette\Application\BadRequestException;

/**
 * Class OutliersTasksPresenter - presenter pro práci s outliery
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 */
class OutliersTasksPresenter extends BaseResourcePresenter {
  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;

  #region actionRead
  /**
   * Akce vracející detaily konkrétní úlohy
   * @param int $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Outliers"},
   *   path="/outliers-tasks/{id}",
   *   summary="Get outlier detection task details",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="OutliersTask ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Outlier detection task state",
   *     @SWG\Schema(ref="#/definitions/OutliersTaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested outlier detection task was not found.")
   * )
   */
  public function actionRead($id){
    throw new NotImplementedException();//FIXME
  }
  #endregion actionRead


  /**
   * Akce vracející přehled outlierů z již vyřešené úlohy
   * @param int $id
   * @param int $limit - informace o počtu outlierů, které chceme vrátit
   * @param int $offset = 0 - informace o počtu outlierů, které se mají přeskočit
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Outliers"},
   *   path="/outliers-tasks/{id}/outliers",
   *   summary="Get list of outliers - results of outlier detection task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="OutliersTask ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Parameter(
   *     name="offset",
   *     description="Offset",
   *     required=false,
   *     default=0,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="limit",
   *     description="Limit (top outliers count)",
   *     required=true,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Results - list of outliers",
   *     @SWG\Schema(
   *       @SWG\Property(property="outliersTask",ref="#/definitions/OutliersTaskResponseWithOffsetAndLimit"),
   *       @SWG\Property(
   *         property="outlier",
   *         type="array"
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested outlier detection task was not found.")
   * )
   */
  public function actionReadOutliers($id, $limit, $offset=0){
    throw new NotImplementedException();//FIXME
  }


  /**
   * Akce pro zadání nové úlohy detekce outlierů
   * @SWG\Post(
   *   tags={"Outliers"},
   *   path="/outliers-tasks",
   *   summary="Create new outlier detection task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     description="Parameters of outlier detection task",
   *     name="task",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/OutliersTaskInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Outlier detection task created",
   *     @SWG\Schema(ref="#/definitions/OutliersTaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionCreate(){
    throw new NotImplementedException();//FIXME
  }


  #region actionStart/actionState
  /**
   * Akce pro spuštění dolování konkrétní úlohy
   * @param int $id
   * @SWG\Get(
   *   tags={"Outliers"},
   *   path="/outliers-tasks/{id}/start",
   *   summary="Start the solving of the outlier detection task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="OutliersTask ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Outlier detection task state",
   *     @SWG\Schema(
   *       ref="#/definitions/OutliersTaskResponse"
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested outlier detection task was not found.")
   * )
   */
  public function actionReadStart($id) {
    //TODO implementovat...
    throw new NotImplementedException();
    /*

    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task, $this->currentUser);
    if ($task->state==Task::STATE_NEW){
      //runTask
      $taskState=$miningDriver->startMining();
      $this->tasksFacade->updateTaskState($task,$taskState);

      //spustíme background požadavek na kontrolu stavu úlohy (jestli je dokončená atp.
      $backgroundImportUrl=$this->getAbsoluteLink('readMiningCheckState',['id'=>$id,'relation'=>'miningCheckState','timeout'=>time()+self::MINING_TIMEOUT_INTERVAL],Link::SELF,true);
      RequestHelper::sendBackgroundGetRequest($backgroundImportUrl);

      //send task simple details
      $this->resource=$task->getDataArr(false);
      $this->sendResource();
    }else{
      $this->forward('readState',['id'=>$id]);
    }
    */
  }

  /**
   * Akce pro spuštění dolování konkrétní úlohy
   * @param int $id
   * @SWG\Get(
   *   tags={"Outliers"},
   *   path="/outliers-tasks/{id}/state",
   *   summary="Check state of the data mining task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="OutliersTask ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Outlier detection task state",
   *     @SWG\Schema(
   *       ref="#/definitions/OutliersTaskResponse"
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested outlier detection task was not found.")
   * )
   */
  public function actionReadState($id) {
    throw new NotImplementedException();//FIXME
    /*
    $task=$this->findTaskWithCheckAccess($id);
    //send task simple details
    $this->resource=$task->getDataArr(false);
    $this->sendResource();
    */
  }
  #endregion actionStart/actionState

  #region actionDelete
  /**
   * Akce pro smazání
   * @param int $id
   * @SWG\Delete(
   *   tags={"Outliers"},
   *   path="/outliers-tasks/{id}",
   *   summary="Delete outlier detection task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="OutliersTask ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Outlier detection task created",
   *     @SWG\Schema(ref="#/definitions/OutliersTaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionDelete($id){
    throw new NotImplementedException();//FIXME
  }
  #endregion actionDelete




  #region injections
  /**
   * @param XmlSerializersFactory $xmlSerializersFactory
   */
  public function injectXmlSerializersFactory(XmlSerializersFactory $xmlSerializersFactory) {
    $this->xmlSerializersFactory=$xmlSerializersFactory;
  }
  /**
   * @param XmlTransformator $xmlTransformator
   */
  public function injectXmlTransformator(XmlTransformator $xmlTransformator){
    $this->xmlTransformator=$xmlTransformator;
    //nastaven basePath
    /** @noinspection PhpUndefinedFieldInspection */
    $this->xmlTransformator->setBasePath($this->template->basePath);
  }
  #endregion injections

  /**
   * @SWG\Definition(
   *   definition="OutliersTaskInput",
   *   title="OutliersTaskConfig",
   *   required={"miner","minSupport"},
   *   @SWG\Property(property="miner",type="integer",description="ID of the miner for this task"),
   *   @SWG\Property(property="minSupport",type="float",default=0,description="Requested minimal support")
   * )
   * @SWG\Definition(
   *   definition="OutliersTaskResponseWithOffsetAndLimit",
   *   title="OutliersTaskInfoWithOffsetAndLimit",
   *   required={"id","minSupport","state","offset","limit"},
   *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
   *   @SWG\Property(property="minSupport",type="float",default=0,description="Minimal support used for detection of outliers"),
   *   @SWG\Property(property="state",type="string",description="State of the task"),
   *   @SWG\Property(property="offset",type="integer",default=0,description="Offset"),
   *   @SWG\Property(property="limit",type="integer",default=0,description="Limit")
   * )
   */
}
