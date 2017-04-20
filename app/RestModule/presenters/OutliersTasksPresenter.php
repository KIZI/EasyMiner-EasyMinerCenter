<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Resource\Link;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Libs\RequestHelper;
use EasyMinerCenter\Model\Mining\Entities\Outlier;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Facades\OutliersTasksFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use EasyMinerCenter\Model\Mining\Exceptions\OutliersTaskInvalidArgumentException;
use Nette\Application\BadRequestException;

/**
 * Class OutliersTasksPresenter - presenter pro práci s outliery
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 */
class OutliersTasksPresenter extends BaseResourcePresenter {
  use MinersFacadeTrait;

  /** @const MINING_STATE_CHECK_INTERVAL - doba čekání mezi kontrolami stavu úlohy (v sekundách) */
  const MINING_STATE_CHECK_INTERVAL=1;
  /** @const MINING_TIMEOUT_INTERVAL - časový interval pro dokončení dolování od timestampu spuštění úlohy (v sekundách) */
  const MINING_TIMEOUT_INTERVAL=600;

  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;
  /** @var  OutliersTasksFacade $outliersTasksFacade */
  private $outliersTasksFacade;

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
    $outliersTask=$this->findOutliersTaskWithCheckAccess($id);
    //TODO kontrola, jestli je outlierstask pořád dostupná na serveru

    $this->setXmlMapperElements('outliersTask');
    $this->resource=$outliersTask->getDataArr();
    $this->sendResource();
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
   *         type="array",
   *         @SWG\Items(ref="#/definitions/OutlierDetails")
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested outlier detection task was not found or results are not available.")
   * )
   */
  public function actionReadOutliers($id, $limit, $offset=0){
    /** @var OutliersTask $task */
    $task=$this->findOutliersTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getOutliersTaskMiningDriver($task,$this->currentUser);
    if ($task->state==OutliersTask::STATE_SOLVED){
      //TODO kontrola, jestli je úloha dostupná na serveru

      /** @var Outlier[] $outliers */
      $outliers=$miningDriver->getOutliersTaskResults($limit,$offset);

      $this->resource=[
        'outliersTask'=>$task->getDataArr(),
        'outlier'=>$outliers
      ];

      $this->sendResource();
    }else{
      $this->error('Outliers task results are not available!',404);
    }
  }

  #region actionCreate
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
    $inputData=$this->input->getData();
    $miner=$this->findMinerWithCheckAccess($inputData['miner']);
    try{
      /** @var OutliersTask $outliersTask */
      $outliersTask=$this->outliersTasksFacade->findOutliersTaskByParams($miner,$inputData['minSupport']);
    }catch(\Exception $e){
      $outliersTask=new OutliersTask();
      $outliersTask->miner=$miner;
      $outliersTask->type=$miner->type;
      $outliersTask->minSupport=$inputData['minSupport'];
    }
    if (!empty($outliersTask->type) && in_array($outliersTask->type,[OutliersTask::STATE_INVALID, OutliersTask::STATE_FAILED])){
      $outliersTask->state=OutliersTask::STATE_NEW;
    }
    $this->outliersTasksFacade->saveOutliersTask($outliersTask);
    $this->resource=$outliersTask->getDataArr();
    $this->setXmlMapperElements('outliersTask');
    $this->sendResource();
  }

  /**
   * Validace vstupních parametrů pro vytvoření nové úlohy
   */
  public function validateCreate(){
    $currentUser=$this->getCurrentUser();
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('miner')
      ->addRule(IValidator::REQUIRED,'Miner ID is required!')
      ->addRule(IValidator::CALLBACK,'Requested miner is not available!',function($value)use($currentUser){
        try{
          $miner=$this->minersFacade->findMiner($value);
        }catch(\Exception $e){
          return false;
        }
        if (!($miner->getUserId()==$currentUser->userId)){return false;}
        return $miner->metasource->state==Metasource::STATE_AVAILABLE;
      });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('minSupport')
      ->addRule(IValidator::REQUIRED,'Min value of support is required!')
      ->addRule(IValidator::RANGE,'Min value of support has to be in interval [0;1]!',[0,1]);
  }
  #endregion actionCreate

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
    /** @var OutliersTask $task */
    $task=$this->findOutliersTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getOutliersTaskMiningDriver($task,$this->currentUser);
    if ($task->state==OutliersTask::STATE_NEW){
      //run task
      $taskState=$miningDriver->startMining();
      $this->outliersTasksFacade->updateTaskState($task,$taskState);

      //spustíme background požadavek na kontrolu stavu úlohy (jestli je dokončená atp.
      $backgroundImportUrl=$this->getAbsoluteLink('readMiningCheckState',['id'=>$id,'relation'=>'miningCheckState','timeout'=>time()+self::MINING_TIMEOUT_INTERVAL],Link::SELF,true);
      RequestHelper::sendBackgroundGetRequest($backgroundImportUrl);

      //send task simple details
      $this->setXmlMapperElements('outliersTask');
      $this->resource=$task->getDataArr();
      $this->sendResource();

    }else{
      $this->forward('readState',['id'=>$id]);
    }
  }

  /**
   * Action for check of the task state
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
    /** @var OutliersTask $task */
    $task=$this->findOutliersTaskWithCheckAccess($id);
    $this->setXmlMapperElements('outliersTask');
    $this->resource=$task->getDataArr();
    $this->sendResource();
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
   *     response=200,
   *     description="Outlier detection task deleted",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionDelete($id){
    $outliersTask=$this->findOutliersTaskWithCheckAccess($id);
    $outliersTaskMiningDriver=$this->minersFacade->getOutliersTaskMiningDriver($id,$this->currentUser);
    try{
      $outliersTaskMiningDriver->deleteOutliersTask();
    }catch(OutliersTaskInvalidArgumentException $e){
      $this->error('Outlier detection task cannot be removed now.',403);
    }
    $this->setXmlMapperElements('response');
    $this->outliersTasksFacade->deleteOutliersTask($outliersTask);
    $this->resource=['code'=>200,'status'=>'OK','message'=>'Outlier detection task deleted: '.$outliersTask->outliersTaskId];
    $this->sendResource();
  }
  #endregion actionDelete

  /**
   * Akce pro periodickou kontrolu stavu úlohy na serveru
   * @param int $id - ID úlohy, kterou chceme zkontrolovat
   */
  public function actionReadMiningCheckState($id) {
    //zakážeme ukončení skriptu při zavření přenosového kanálu
    RequestHelper::ignoreUserAbort();

    //nejprve pozastavíme běh skriptu (abychom měli nějakou pauzu mezi jednotlivými kontrolami stavu úlohy)
    sleep(self::MINING_STATE_CHECK_INTERVAL);

    //najdeme úlohu a mining driver
    $task=$this->findOutliersTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getOutliersTaskMiningDriver($task,$this->currentUser);

    //zkontrolujeme stav vzdálené úlohy a zaktualizujeme ho
    $taskState=$miningDriver->checkOutliersTaskState();
    $this->outliersTasksFacade->updateTaskState($task,$taskState);

    #region akce v závislosti na aktuálním běhu úlohy
    if ($taskState->state==OutliersTask::STATE_IN_PROGRESS){
      //odešleme samostatný požadavek na opakované načtení této akce (se všemi stávajícími parametry)
      RequestHelper::sendBackgroundGetRequest($this->getAbsoluteLink('self',[],Link::SELF,true));
    }
    #endregion
    $this->sendTextResponse(time().' DONE '.$this->action);
  }





  /**
   * Funkce pro nalezení úlohy dle zadaného ID a kontrolu oprávnění aktuálního uživatele pracovat s daným minerem
   *
   * @param int $outliersTaskId
   * @return OutliersTask
   * @throws \Nette\Application\BadRequestException
   */
  protected function findOutliersTaskWithCheckAccess($outliersTaskId){
    try{
      /** @var OutliersTask $outliersTask */
      $outliersTask=$this->outliersTasksFacade->findOutliersTask($outliersTaskId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested outlier detection task was not found.');
      return null;
    }
    $this->minersFacade->checkMinerAccess($outliersTask->miner,$this->getCurrentUser());
    return $outliersTask;
  }


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
  /**
   * @param OutliersTasksFacade $outliersTasksFacade
   */
  public function injectOutliersTasksFacade(OutliersTasksFacade $outliersTasksFacade){
    $this->outliersTasksFacade=$outliersTasksFacade;
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
   * @SWG\Definition(
   *   definition="OutlierDetails",
   *   title="OutlierDetails",
   *   required={"id","score","attributeValues"},
   *   @SWG\Property(property="id",type="integer",default=0),
   *   @SWG\Property(property="score",type="float",default=0),
   *   @SWG\Property(property="attributeValues",type="object")
   * )
   */
}
