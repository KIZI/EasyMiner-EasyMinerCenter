<?php

namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\MetasourceTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use Nette\Application\BadRequestException;

/**
 * Class MinersPresenter - presenter for work with Miners
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MinersPresenter extends BaseResourcePresenter{
  use MinersFacadeTrait;

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;

  /** @var null|Datasource $datasource */
  private $datasource=null;
  /** @var null|RuleSet $ruleSet */
  private $ruleSet=null;

  /**
   * Action for reading details about one miner (with the given $id) or reading a list of all miners associated with the current user
   * @param int|null $id = null
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners/{id}",
   *   summary="Get miner details",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response="200",
   *     description="Miner details.",
   *     @SWG\Schema(ref="#/definitions/MinerResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionRead($id=null){
    $this->setXmlMapperElements('miner');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);
    $this->resource=$miner->getDataArr();
    $this->sendResource();
  }

  /**
   * Action returning list of all miners associated with the current user
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners",
   *   summary="Get list of miners for the current user",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Response(
   *     response="200",
   *     description="List of miners",
   *     @SWG\Schema(
   *       type="array",
   *       @SWG\Items(
   *         ref="#/definitions/MinerBasicResponse"
   *       )
   *     )
   *   )
   * )
   */
  public function actionList() {
    $this->setXmlMapperElements('miners','miner');
    $currentUser=$this->getCurrentUser();
    $miners=$this->minersFacade->findMinersByUser($currentUser);
    $result=[];
    if (!empty($miners)){
      foreach ($miners as $miner){
        $result[]=[
          'id'=>$miner->minerId,
          'name'=>$miner->name,
          'type'=>$miner->type
        ];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }

  /**
   * Action for reading a list of all task associated with one selected miner
   * @param $id
   * @throws BadRequestException
   *
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners/{id}/tasks",
   *   summary="Get list of tasks in the selected miner",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="List of tasks",
   *     @SWG\Schema(
   *       @SWG\Property(property="miner",ref="#/definitions/MinerBasicResponse"),
   *       @SWG\Property(
   *         property="task",
   *         type="array",
   *         @SWG\Items(ref="#/definitions/TaskBasicResponse")
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionReadTasks($id) {
    $this->setXmlMapperElements('miner');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);

    $result=[
      'miner'=>[
        'id'=>$miner->minerId,
        'name'=>$miner->name,
        'type'=>$miner->type
      ],
      'task'=>[]
    ];
    $tasks=$miner->tasks;
    if (!empty($tasks)){
      foreach ($tasks as $task){
        $result['task'][]=[
          'id'=>$task->taskId,
          'name'=>$task->name,
          'state'=>$task->state,
          'rulesCount'=>$task->rulesCount
        ];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }

  /**
   * Action for reading a list of all outlier detection task associated with one selected miner
   * @param $id
   * @throws BadRequestException
   *
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners/{id}/outliersTasks",
   *   summary="Get list of tasks in the selected miner",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="List of outlier tasks",
   *     @SWG\Schema(
   *       @SWG\Property(property="miner",ref="#/definitions/MinerBasicResponse"),
   *       @SWG\Property(
   *         property="outlierTask",
   *         type="array",
   *         @SWG\Items(ref="#/definitions/OutliersTaskResponse")
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionReadOutliersTasks($id) {
    $this->setXmlMapperElements('miner');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);
    //TODO kontrola, jestli jsou dané úlohy pořád ještě dostupné
    $result=[
      'miner'=>[
        'id'=>$miner->minerId,
        'name'=>$miner->name,
        'type'=>$miner->type
      ],
      'outliersTask'=>[]
    ];
    $outliersTasks=$miner->outliersTasks;
    if (!empty($outliersTasks)){
      foreach ($outliersTasks as $outliersTask){
        $result['outliersTask'][]=[
          'id'=>$outliersTask->outliersTaskId,
          'minSupport'=>$outliersTask->minSupport,
          'state'=>$outliersTask->state
        ];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }

  #region actionCreate
  /**
   * Action for creating a new miner
   * @SWG\Post(
   *   tags={"Miners"},
   *   path="/miners",
   *   summary="Create new miner",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json", "application/xml"},
   *   consumes={"application/json", "application/xml"},
   *   @SWG\Parameter(
   *     description="Miner",
   *     name="miner",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/MinerInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Miner created successfully, returns details of Miner.",
   *     @SWG\Schema(ref="#/definitions/MinerResponse")
   *   ),
   *   @SWG\Response(
   *     response=422,
   *     description="Sent values are not acceptable!",
   *     @SWG\Schema(ref="#/definitions/InputErrorResponse")
   *   )
   * )
   */
  public function actionCreate(){
    //prepare Miner from input values
    /** @var Miner $miner */
    $miner=new Miner();
    /** @var array $data */
    $data=$this->input->getData();
    $miner->name=$data['name'];
    $miner->type=$data['type'];
    $miner->datasource=$this->datasource;

    if (!empty($this->ruleSet)){
      $miner->ruleSet=$this->ruleSet;
    }
    $miner->created=new \DateTime();
    $miner->user=$this->getCurrentUser();
    $this->minersFacade->saveMiner($miner);

    $metasourceTask=$this->metasourcesFacade->startMinerMetasourceInitialization($miner, $this->minersFacade);
    while($metasourceTask && $metasourceTask->state!=MetasourceTask::STATE_DONE){
      $metasourceTask=$this->metasourcesFacade->initializeMinerMetasource($miner, $metasourceTask);
      usleep(100);
    }

    $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);
    //send response
    $this->actionRead($miner->minerId);
  }

  /**
   * Method for checking of input params for actionCreate()
   */
  public function validateCreate() {
    $currentUser=$this->getCurrentUser();
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('name')->addRule(IValidator::REQUIRED,'Name is required!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('type')
      ->addRule(IValidator::REQUIRED,'Miner type is required!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('datasourceId')
      ->addRule(IValidator::REQUIRED,'Datasource ID is required!')
      ->addRule(IValidator::CALLBACK,'Requested datasource was not found, or is not accessible!', function($value)use($currentUser){
          try{
            $this->datasource=$this->datasourcesFacade->findDatasource($value);
            if ($this->datasourcesFacade->checkDatasourceAccess($this->datasource,$currentUser)){
              //check the availability of the selected compatible miner with the given datasource
              $availableMinerTypes = $this->minersFacade->getAvailableMinerTypes($this->datasource->type);
              return (!empty($availableMinerTypes[$this->getInput()->getData()['type']]));
            }else{
              return false;
            }
          }catch (\Exception $e){
            return false;
          }
        });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('ruleSetId')
      ->addRule(IValidator::CALLBACK,'Requested rule set was not found, or is not accessible!',function($value)use($currentUser){
        if (empty($value)){return true;}
        try{
          $this->ruleSet=$this->ruleSetsFacade->findRuleSet($value);
          $this->ruleSetsFacade->checkRuleSetAccess($this->ruleSet,$currentUser);
          return true;
        }catch (\Exception $e){
          return false;
        }
      });
  }
  #endregion actionCreate

  #region actionDelete
  /**
   * Action for deleting of the selected miner
   * @param int $id
   * @throws BadRequestException
   * @SWG\Delete(
   *   tags={"Miners"},
   *   path="/miners/{id}",
   *   summary="Delete miner with all tasks",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response="200",
   *     description="Miner deleted.",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionDelete($id){
    $this->setXmlMapperElements('result');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);
    $this->minersFacade->deleteMiner($miner);
    $this->resource=['200','OK','Miner deleted: '.$miner->minerId];
    $this->sendResource();
  }
  #endregion actionDelete

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade) {
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade) {
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade){
    $this->metasourcesFacade=$metasourcesFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="MinerResponse",
 *   title="Miner",
 *   required={"id","name","type","datasourceId","metasourceId","ruleSetId"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Miner type",enum={"r","lm"}),
 *   @SWG\Property(property="datasourceId",type="integer",description="ID of used datasource"),
 *   @SWG\Property(property="metasourceId",type="integer",description="ID of used metasource"),
 *   @SWG\Property(property="ruleSetId",type="integer",description="ID of rule set associated with the miner"),
 *   @SWG\Property(property="created",type="dateTime",description="DateTime of miner creation"),
 *   @SWG\Property(property="lastOpened",type="dateTime",description="DateTime of miner last open action")
 * )
 * @SWG\Definition(
 *   definition="MinerBasicResponse",
 *   title="MinerBasicInfo",
 *   required={"id","name","type"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Type of data mining backend",enum={"cloud","r","lm"})
 * )
 * @SWG\Definition(
 *   definition="MinerInput",
 *   title="Miner",
 *   required={"name","type","datasourceId"},
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Type of data mining backend",enum={"cloud","r","lm"}),
 *   @SWG\Property(property="datasourceId",type="integer",description="ID of existing datasource"),
 *   @SWG\Property(property="ruleSetId",type="integer",description="ID of existing rule set (optional)"),
 * )
 * @SWG\Definition(
 *   definition="TaskBasicResponse",
 *   title="TaskBasicInfo",
 *   required={"id","name","state"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="state",type="string",description="State of the task"),
 *   @SWG\Property(property="rulesCount",type="integer",description="Count of founded rules")
 * )
 * @SWG\Definition(
 *   definition="OutliersTaskResponse",
 *   title="OutliersTaskInfo",
 *   required={"id","minSupport","state"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
 *   @SWG\Property(property="minSupport",type="float",default=0,description="Minimal support used for detection of outliers"),
 *   @SWG\Property(property="state",type="string",description="State of the task")
 * )
 */