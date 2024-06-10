<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\InvalidStateException;
use Drahak\Restful\NotImplementedException;
use Drahak\Restful\Resource\Link;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Libs\RequestHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\Pmml42Serializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\PmmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use Nette\Application\BadRequestException;

/**
 * Class TasksPresenter - presenter for work with rule mining tasks
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 */
class TasksPresenter extends BaseResourcePresenter {
  use TasksFacadeTrait;
  /** @const MINING_STATE_CHECK_INTERVAL - length of sleep interval between checks of the task state (in seconds) */
  const MINING_STATE_CHECK_INTERVAL=1;
  /** @const MINING_TIMEOUT_INTERVAL - length of max task solving duration (in seconds) */
  const MINING_TIMEOUT_INTERVAL=600;
  /** @var RulesFacade $rulesFacade */
  private $rulesFacade;

  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;

  #region actionReadPmml,actionReadHtml
  /**
   * Action returning PMML export of a concrete task
   * @param int $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/pmml",
   *   summary="Get PMML export of the task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Parameter(
   *     name="type",
   *     description="PMML type",
   *     required=false,
   *     type="string",
   *     in="query",
   *     default="guha",
   *     enum={"guha","associationmodel","associationmodel-4.2"}
   *   ),
   *   @SWG\Parameter(
   *     name="frequencies",
   *     description="Include frequencies",
   *     required=false,
   *     type="string",
   *     in="query",
   *     default="yes",
   *     enum={"yes","no"}
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task PMML"
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found."),
   *   @SWG\Response(response=500, description="Task has not been solved.")
   * )
   */
  public function actionReadPmml($id){
    $task=$this->findTaskWithCheckAccess($id);

    if ($task->state!=Task::STATE_SOLVED){
      throw new InvalidStateException("Task has not been solved!");
    }
    $inputData=$this->getInput()->getData();
    if (empty($inputData['type'])){
      $outputType='guha';
    }else{
      $outputType=strtolower($inputData['type']);
    }
    $includeFrequencies=(!empty($inputData['frequencies'])&&(strtolower($inputData['frequencies'])=='yes'));

    //region output selection
    switch($outputType){
      case 'associationmodel':
        //serializace AssociationRules XML
        $pmml=$this->prepareTaskARPmml($task,$includeFrequencies,false);
        break;
      case 'associationmodel-4.2':
      case 'associationmodel42':
        //serializace standard PMML 4.2
        $pmml=$this->prepareTaskARPmml($task,$includeFrequencies,true);
        break;
      case 'guha':
      default:
        //serializace GUHA PMML
        $pmml=$this->prepareTaskGuhaPmml($task,$includeFrequencies);
    }
    //endregion output selection

    $this->sendXmlResponse($pmml);
  }

  /**
   * Action returning HTML representation of a task details
   * @param int $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/html",
   *   summary="Get HTML export of the task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task HTML details"
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found."),
   *   @SWG\Response(response=500, description="Task has not been solved.")
   * )
   */
  public function actionReadHtml($id){
    $task=$this->findTaskWithCheckAccess($id);

    if ($task->state!=Task::STATE_SOLVED){
      throw new InvalidStateException("Task has not been solved!");
    }

    //serialize GUHA PMML and transform it
    $pmml=$this->prepareTaskGuhaPmml($task);
    $this->sendHtmlResponse($this->template->content=$this->xmlTransformator->transformToHtml($pmml));
  }

  /**
   * @param Task $task
   * @param bool $includeFrequencies = true
   * @param bool $version42 = false
   * @return \SimpleXMLElement
   */
  private function prepareTaskARPmml(Task $task, $includeFrequencies=true, $version42=false){
    if ($version42){
      /** @var Pmml42Serializer $pmmlSerializer */
      $pmmlSerializer=$this->xmlSerializersFactory->createPmml42Serializer($task);
    }else{
      /** @var PmmlSerializer $pmmlSerializer */
      $pmmlSerializer=$this->xmlSerializersFactory->createPmmlSerializer($task);
      $pmmlSerializer->appendDataDictionary($includeFrequencies);
      $pmmlSerializer->appendTransformationDictionary($includeFrequencies);
    }
    $pmmlSerializer->appendRules();
    return $pmmlSerializer->getPmml();
  }
  /**
   * @param Task $task
   * @param bool $includeFrequencies=true
   * @return \SimpleXMLElement
   */
  private function prepareTaskGuhaPmml(Task $task, $includeFrequencies=true){
    /** @var Metasource $metasource */
    $pmmlSerializer=$this->xmlSerializersFactory->createGuhaPmmlSerializer($task,null);
    $pmmlSerializer->appendTaskSettings();
    $pmmlSerializer->appendDataDictionary($includeFrequencies);
    $pmmlSerializer->appendTransformationDictionary($includeFrequencies);
    $pmmlSerializer->appendRules();
    return $pmmlSerializer->getPmml();
  }
  #endregion

  #region actionRead
  /**
   * Action returning task details
   * @param int $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}",
   *   summary="Get task details",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task details",
   *     @SWG\Schema(ref="#/definitions/TaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionRead($id){
    $task=$this->findTaskWithCheckAccess($id);
    $this->resource=$task->getDataArr(true);
    $this->sendResource();
  }
  #endregion

  #region actionSimple
  /**
   * Action for creating of a new task
   * @param string $id=null|'simple' - parametr používaný pouze v případě, kdy má dojít ke zjednodušenému vytváření úlohy
   * @throws NotImplementedException
   */
  public function actionCreate($id=null) {
    if ($id=='simple'){
      $this->forward('simple');
    }
    //TODO implementovat podporu zadání komplexní úlohy
    throw new NotImplementedException();
  }

  /**
   * Method for validation of input params for actionCreate()
   * @param null|string $id=null (pokud $id=="simple", dojde k přesměrování na funkci validateSimple)
   * @throws NotImplementedException
   */
  public function validateCreate($id=null) {
    if ($id=='simple'){$this->forward('simple');return;}
    //TODO implementovat podporu zadání komplexní úlohy
    throw new NotImplementedException();
  }

  /**
   * Action for creating a new rule mining task with simple configuration
   * @SWG\Post(
   *   tags={"Tasks"},
   *   path="/tasks/simple",
   *   summary="Create new simple configured task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     description="SimpleTask",
   *     name="task",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/TaskSimpleInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Task created",
   *     @SWG\Schema(ref="#/definitions/TaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionSimple() {
    $inputData=$this->input->getData();
    $miner=$this->findMinerWithCheckAccess($inputData['miner']);
    $task=$this->tasksFacade->prepareSimpleTask($miner, $inputData);
    $this->tasksFacade->saveTask($task);
    //send task details
    $this->resource=$task->getDataArr(true);
    $this->sendResource();
  }

  /**
   * Method for validation of the input for actionSimple()
   */
  public function validateSimple() {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('miner')->addRule(IValidator::CALLBACK,'You cannot use the given miner, or the miner has not been found!',function($value) {
      try {
        /*$miner=*/$this->findMinerWithCheckAccess($value);
        return true;
      } catch(\Exception $e) {
        return false;
      }
    });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('name')->addRule(IValidator::REQUIRED,'You have to input the task name!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('limitHits')
      ->addRule(IValidator::INTEGER,'Max rules count (limitHits) has to be positive integer!')
      ->addRule(IValidator::RANGE,'Max rules count (limitHits) has to be positive integer!',[1,null]);
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('IMs')
      ->addRule(IValidator::CALLBACK,'You have to input interest measure thresholds!',function($value){
        return count($value)>0;
      });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('consequent')
      ->addRule(IValidator::CALLBACK,'You have to input the structure of consequent!',function($value){
        return (count($value)>0);
      });
  }
  #endregion actionSimple

  #region actionStart/actionStop/actionState
  /**
   * Action for start of solving of a data mining task
   * @param int $id
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/start",
   *   summary="Start the solving of the data mining task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task state",
   *     @SWG\Schema(
   *       ref="#/definitions/TaskSimpleResponse"
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionReadStart($id) {
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task, $this->currentUser);
    if ($task->state==Task::STATE_NEW){
      //runTask
      $taskState=$miningDriver->startMining();
      $this->tasksFacade->updateTaskState($task,$taskState);

      //run background request for the task state check
      $backgroundImportUrl=$this->getAbsoluteLink('readMiningCheckState',['id'=>$id,'relation'=>'miningCheckState','timeout'=>time()+self::MINING_TIMEOUT_INTERVAL],Link::SELF,true);
      RequestHelper::sendBackgroundGetRequest($backgroundImportUrl);

      //send task simple details
      $this->resource=$task->getDataArr(false);
      $this->sendResource();
    }else{
      $this->forward('readState',['id'=>$id]);
    }
  }

  /**
   * Action for checking of a task state
   * @param int $id
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/state",
   *   summary="Check state of the data mining task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task state",
   *     @SWG\Schema(
   *       ref="#/definitions/TaskSimpleResponse"
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionReadState($id) {
    $task=$this->findTaskWithCheckAccess($id);
    //send task simple details
    $this->resource=$task->getDataArr(false);
    $this->sendResource();
  }

  /**
   * Action for stopping of a running task
   * @param int $id
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/stop",
   *   summary="Stop the solving of the data mining task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task state",
   *     @SWG\Schema(
   *       ref="#/definitions/TaskSimpleResponse"
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionReadStop($id) {
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->currentUser);
    //stop the run
    $taskState=$miningDriver->stopMining();
    $this->tasksFacade->updateTaskState($task,$taskState);
    //send task simple details
    $this->resource=$task->getDataArr(false);
    $this->sendResource();
  }
  #endregion actionStart/actionStop/actionState

  #region actions for periodical check of mining state and background import of results
  /**
   * Action for periodical check of a task state on the remote server (using mining driver)
   * @param int $id - ID úlohy, kterou chceme zkontrolovat
   * @param int $timeout = 0 - timestamp, kdy dojde k neúspěšnému ukončení úlohy
   */
  public function actionReadMiningCheckState($id,$timeout=0) {
    //ignore the user disconnection (it it action for background requests)
    RequestHelper::ignoreUserAbort();
    //sleep (to get a pause between the state checks)
    sleep(self::MINING_STATE_CHECK_INTERVAL);
    //find the task and the appropriate mining driver
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->currentUser);
    //check the state of the task on remote server and update the info about the state
    $taskState=$miningDriver->checkTaskState();
    $this->tasksFacade->updateTaskState($task,$taskState);

    #region actions selected by the task state
    if ($taskState->importState==Task::IMPORT_STATE_WAITING){
      //we have a waiting import there on the server, try to send request to import these data
      $backgroundImportUrl=$this->getAbsoluteLink('readMiningImportResults',['id'=>$id,'relation'=>'miningImportResults'],Link::SELF,true);
      RequestHelper::sendBackgroundGetRequest($backgroundImportUrl);
    }
    if ($taskState->state==Task::STATE_IN_PROGRESS){
      if ($timeout>0 && $timeout<time()){
        //task run timeouted => cancel the task
        $this->forward('readStop',$id);
      }else{
        //send a standalone request for repeated load of this action (with the same params)
        RequestHelper::sendBackgroundGetRequest($this->getAbsoluteLink('self',[],Link::SELF,true));
      }
    }
    #endregion actions selected by the task state
    $this->sendTextResponse(time().' DONE '.$this->action);
  }

  /**
   * Action for import of results
   * @param int
   */
  public function actionReadMiningImportResults($id) {
    //ignore the user disconnection (it it action for background requests)
    RequestHelper::ignoreUserAbort();
    //find the task and the appropriate mining driver
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->currentUser);

    //the import should be done if there is no other import running (for the same task)
    if ($task->importState==Task::IMPORT_STATE_WAITING){
      //mark running partial import
      $taskState=$task->getTaskState();
      $taskState->importState=Task::IMPORT_STATE_PARTIAL;
      $this->tasksFacade->updateTaskState($task,$taskState);
      //import full PMML and update the task state
      $taskState=$miningDriver->importResultsPMML();
      $this->tasksFacade->updateTaskState($task,$taskState);

      //execute next partial request
      sleep(self::MINING_STATE_CHECK_INTERVAL);//TODO testovací
      RequestHelper::sendBackgroundGetRequest($this->getAbsoluteLink('self',[],Link::SELF,true));
    }

    $this->sendTextResponse(time().' DONE '.$this->action);
  }
  #endregion actions for periodical check of mining state and background import of results


  #region actionReadRules
  /**
   * Action for reading of rules from a selected task
   * @param int $id
   * @param string|null $orderby
   * @param int|null $offset
   * @param int|null $limit
   * @param string|null $search
   * @param int|null $minConf=null
   * @param int|null $maxConf=null
   * @param int|null $minSupp=null
   * @param int|null $maxSupp=null
   * @param int|null $minLift=null
   * @param int|null $maxLift=null
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/rules",
   *   summary="List rules founded using the selected task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Parameter(
   *     name="orderby",
   *     description="Order rules by",
   *     required=false,
   *     type="string",
   *     enum={"default","conf","supp","lift"},
   *     default="default",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="order",
   *     description="Order rules collation",
   *     required=false,
   *     type="string",
   *     enum={"ASC","DESC"},
   *     default="ASC",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="offset",
   *     description="Paginator offset",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="limit",
   *     description="Limit rules count",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="search",
   *     description="Search in rule text",
   *     required=false,
   *     type="string",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="searchAntecedent",
   *     description="Search in rule text",
   *     required=false,
   *     type="string",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="searchConsequent",
   *     description="Search in rule text",
   *     required=false,
   *     type="string",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="minConf",
   *     description="Filter rules by minimal value of confidence",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="maxConf",
   *     description="Filter rules by maximal value of confidence",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="minSupp",
   *     description="Filter rules by minimal value of support",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="maxSupp",
   *     description="Filter rules by maximal value of support",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="minLift",
   *     description="Filter rules by minimal value of lift",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="maxLift",
   *     description="Filter rules by maximal value of lift",
   *     required=false,
   *     type="number",
   *     in="query"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="List of rules",
   *     @SWG\Schema(
   *       @SWG\Property(property="task",ref="#/definitions/TaskSimpleResponse"),
   *       @SWG\Property(property="rulesCount",type="integer",description="Count of rules (corresponding to active search)"),
   *       @SWG\Property(
   *         property="rules",
   *         type="array",
   *         @SWG\Items(ref="#/definitions/TaskRuleResponse")
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found."),
   *   @SWG\Response(response=500, description="Task has not been solved.")
   * )
   */
  public function actionReadRules($id, $orderby=null, $order="ASC", $offset=null, $limit=null, $search=null, $searchAntecedent=null, $searchConsequent=null, $minConf=null, $maxConf=null, $minSupp=null, $maxSupp=null, $minLift=null, $maxLift=null){
    $task=$this->findTaskWithCheckAccess($id);
    $filterIMs=[];
    if ($minConf!=null){
      $filterIMs['minConf']=(float)$minConf;
    }
    if ($maxConf!=null){
      $filterIMs['maxConf']=(float)$maxConf;
    }
    if ($minSupp!=null){
      $filterIMs['minSupp']=(float)$minSupp;
    }
    if ($maxSupp!=null){
      $filterIMs['maxSupp']=(float)$maxSupp;
    }
    if ($minLift!=null){
      $filterIMs['minLift']=(float)$minLift;
    }
    if ($maxLift!=null){
      $filterIMs['maxLift']=(float)$maxLift;
    }

    $searchArr=[];
    if (!empty($search)){
      $searchArr[]=$search;
    }
    if (!empty($searchAntecedent)){
      $searchArr['antecedent']=$searchAntecedent;
    }
    if (!empty($searchAntecedent)){
      $searchArr['consequent']=$searchConsequent;
    }

    $result=[
      'task'=>$task->getDataArr(),
      'rulesCount'=>$this->rulesFacade->findRulesByTaskCount($task, (!empty($searchArr)?$searchArr:null), false, $filterIMs),
      'rules'=>[]
    ];

    if ($result['rulesCount']>0){
      if (!empty($orderby)){
        $orderby=((in_array($orderby,['default','conf','supp','lift']))?$orderby:'default');
        if (strtoupper($order)=='DESC'){
          $orderby.=' DESC';
        }else{
          $orderby.=' ASC';
        }
      }


      $rules=$this->rulesFacade->findRulesByTask($task,$search,$orderby, $offset>0?$offset:null, $limit>0?$limit:null, false, $filterIMs);
      if (!empty($rules)){
        foreach ($rules as $rule){
          $result['rules'][]=$rule->getBasicDataArr();
        }
      }
    }

    $this->resource=$result;
    $this->sendResource();
  }
  #endregion actionReadRules

  #region actionUpdate
  /**
   * Action for update of task config/details
   * @disabled:SWG\Put(
   *   tags={"Tasks"},
   *   path="/tasks/{id}",
   *   summary="Update task details",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     description="TaskBasicInfo",
   *     name="details",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/TaskBasicUpdateInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task updated",
   *     @SWG\Schema(ref="#/definitions/TaskResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionUpdate() {
    //TODO implement
    throw new \Nette\NotImplementedException();
  }

  /**
   * Method for validation of input params for actionUpdate()
   */
  public function validateUpdate() {
    //TODO implement
    throw new \Nette\NotImplementedException();
    //XXX
  }
  #endregion actionUpdate

  #region actionDelete
  /**
   * Action for deleting of a task
   * @param int $id
   * @SWG\Delete(
   *   tags={"Tasks"},
   *   path="/tasks/{id}",
   *   summary="Delete existing task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task deleted.",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found.")
   * )
   */
  public function actionDelete($id) {
    $task=$this->findTaskWithCheckAccess($id);
    if (!$task->isMiningAndImportFinished()){
      //kontrola stavu úlohy (jestli ještě neběží)
      $this->error('Task solving is still in progress.','403');
      return;
    }
    $this->tasksFacade->deleteTask($task);
    $this->resource=['code'=>200,'status'=>'OK','message'=>'Task deleted: '.$task->taskId];
    $this->sendResource();
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
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
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
}


/**
 * @SWG\Definition(
 *   definition="TaskSimpleResponse",
 *   title="TaskSimpleDetails",
 *   required={"id","miner","type","name","state","rulesCount"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
 *   @SWG\Property(property="miner",type="integer",description="ID of the associated miner"),
 *   @SWG\Property(property="type",type="integer",description="Type of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="state",type="string",description="State of the task",enum={"new","in_progress","solved","failed","interrupted"}),
 *   @SWG\Property(property="importState",type="string",description="State of the results import",enum={"none","waiting","partial","done"}),
 *   @SWG\Property(property="rulesCount",type="integer",description="Count of founded rules"),
 *   @SWG\Property(property="rulesOrder",type="string",description="Rules order")
 * )
 * @SWG\Definition(
 *   definition="TaskResponse",
 *   title="Task",
 *   required={"id","miner","type","name","state","rulesCount"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
 *   @SWG\Property(property="miner",type="integer",description="ID of the associated miner"),
 *   @SWG\Property(property="type",type="integer",description="Type of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="state",type="string",description="State of the task",enum={"new","in_progress","solved","failed","interrupted"}),
 *   @SWG\Property(property="importState",type="string",description="State of the results import",enum={"none","waiting","partial","done"}),
 *   @SWG\Property(property="rulesCount",type="integer",description="Count of founded rules"),
 *   @SWG\Property(property="rulesOrder",type="string",description="Rules order"),
 *   @SWG\Property(
 *     property="taskSettings",
 *     description="Structured configuration of the task settings",
 *     @SWG\Property(property="limitHits",type="integer",description="Limit count of rules"),
 *     @SWG\Property(
 *       property="rule0",
 *       description="Rule pattern",
 *       @SWG\Property(property="antecedent",description="Antecedent pattern",ref="#/definitions/CedentDetailsResponse"),
 *       @SWG\Property(property="IMs",type="array",@SWG\Items(ref="#/definitions/TaskIMResponse")),
 *       @SWG\Property(property="succedent",description="Consequent pattern",ref="#/definitions/CedentDetailsResponse")
 *     ),
 *     @SWG\Property(property="strict",type="boolean",description="Strict require attributes in the pattern")
 *   )
 * )
 *
 * @SWG\Definition(
 *   definition="TaskIMResponse",
 *   title="IM",
 *   @SWG\Property(property="name",type="string"),
 *   @SWG\Property(property="localizedName",type="string"),
 *   @SWG\Property(property="thresholdType",type="string"),
 *   @SWG\Property(property="compareType",type="string"),
 *   @SWG\Property(
 *     property="fields",
 *     type="array",
 *     @SWG\Items(ref="#/definitions/TaskConfigFieldDetails")
 *   ),
 *   @SWG\Property(property="threshold",type="number"),
 *   @SWG\Property(property="alpha",type="number"),
 * )
 * @SWG\Definition(
 *   definition="TaskConfigFieldDetails",
 *   title="FieldDetails",
 *   required={"name","value"},
 *   @SWG\Property(property="name",type="string"),
 *   @SWG\Property(property="value",type="number")
 * )
 * @SWG\Definition(
 *   definition="CedentDetailsResponse",
 *   title="CedentDetails",
 *   @SWG\Property(property="type",type="string"),
 *   @SWG\Property(
 *     property="connective",
 *     @SWG\Property(property="id",type="integer"),
 *     @SWG\Property(property="name",type="string"),
 *     @SWG\Property(property="type",type="string"),
 *   ),
 *   @SWG\Property(property="level",type="integer"),
 *   @SWG\Property(property="children",type="array",
 *     @SWG\Items(ref="#/definitions/TaskSettingsAttributeDetails")
 *   ),
 * )
 * @SWG\Definition(
 *   definition="TaskSettingsAttributeDetails",
 *   title="AttributeDetails",
 *   @SWG\Property(property="name",type="string"),
 *   @SWG\Property(property="category",type="string"),
 *   @SWG\Property(property="ref",type="string"),
 *   @SWG\Property(
 *     property="fields",
 *     type="array",
 *     @SWG\Items(ref="#/definitions/TaskConfigFieldDetails")
 *   ),
 *   @SWG\Property(property="sign",type="string",enum={"positive"}),
 * )
 *
 * @SWG\Definition(
 *   definition="TaskRuleResponse",
 *   title="Rule",
 *   required={"id","text"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule"),
 *   @SWG\Property(property="text",type="string",description="Human-readable form of the rule"),
 *   @SWG\Property(property="a",type="string",description="A value from the four field table"),
 *   @SWG\Property(property="b",type="string",description="B value from the four field table"),
 *   @SWG\Property(property="c",type="string",description="C value from the four field table"),
 *   @SWG\Property(property="d",type="string",description="D value from the four field table"),
 *   @SWG\Property(property="selected",type="string",enum={"0","1"},description="1, if the rule is in Rule Clipboard"),
 * )
 *
 *
 * @SWG\Definition(
 *   definition="TaskSimpleInput",
 *   title="TaskSimpleConfig",
 *   required={"miner","name","consequent","IMs"},
 *   @SWG\Property(property="miner",type="integer",description="ID of the miner for this task"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="antecedent",description="Antecedent configuration",ref="#/definitions/CedentSimpleInput"),
 *   @SWG\Property(property="consequent",description="Consequent configuration",ref="#/definitions/CedentSimpleInput"),
 *   @SWG\Property(property="IMs",description="Interest measure thresholds",type="array",
 *     @SWG\Items(ref="#/definitions/IMSimpleInput")
 *   ),
 *   @SWG\Property(property="limitHits",type="integer",description="Limit of requested rules count")
 * )
 * @SWG\Definition(
 *   definition="TaskBasicUpdateInput",
 *   title="TaskBasicUpdate",
 *   required={"name","rulesOrder"},
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="rulesOrder",type="string",description="Rules order (IM name)")
 * )
 * @SWG\Definition(
 *   definition="CedentSimpleInput",
 *   type="array",
 *   @SWG\Items(ref="#/definitions/AttributeSimpleInput")
 * )
 * @SWG\Definition(
 *   definition="AttributeSimpleInput",
 *   required={"name"},
 *   @SWG\Property(property="attribute",type="string",description="Attribute name"),
 *   @SWG\Property(property="fixedValue",type="string",description="Fixed attribute value (optional,leave empty, if *)")
 * )
 * @SWG\Definition(
 *   definition="IMSimpleInput",
 *   required={"name"},
 *   @SWG\Property(property="name",type="string"),
 *   @SWG\Property(property="value",type="number"),
 * )
 *
 */