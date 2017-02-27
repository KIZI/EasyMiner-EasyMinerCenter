<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\InvalidStateException;
use Drahak\Restful\NotImplementedException;
use Drahak\Restful\Resource\Link;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Libs\RequestHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\Pmml42Serializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\PmmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use Nette\Application\BadRequestException;

/**
 * Class TasksPresenter - presenter pro práci s jednotlivými úlohami
 * @package EasyMinerCenter\RestModule\Presenters
 *
 */
class TasksPresenter extends BaseResourcePresenter {
  use TasksFacadeTrait;
  /** @const MINING_STATE_CHECK_INTERVAL - doba čekání mezi kontrolami stavu úlohy (v sekundách) */
  const MINING_STATE_CHECK_INTERVAL=1;
  /** @const MINING_TIMEOUT_INTERVAL - časový interval pro dokončení dolování od timestampu spuštění úlohy (v sekundách) */
  const MINING_TIMEOUT_INTERVAL=600;

  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;

  #region actionReadPmml,actionReadHtml
  /**
   * Akce vracející PMML data konkrétní úlohy
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
   *     description="Task PMML",
   *     @SWG\Schema(type="xml")
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

    //region výběr konkrétního výstupu
    switch($outputType){
      case 'associationmodel':
        //serializace standartního PMML
        $pmml=$this->prepareTaskARPmml($task,$includeFrequencies,false);
        break;
      case 'associationmodel-4.2':
      case 'associationmodel42':
        //serializace standartního PMML ve verzi 4.2
        $pmml=$this->prepareTaskARPmml($task,$includeFrequencies,true);
        break;
      case 'guha':
      default:
        //serializace GUHA PMML
        $pmml=$this->prepareTaskGuhaPmml($task,$includeFrequencies);
    }
    //endregion

    $this->sendXmlResponse($pmml);
  }

  /**
   * Akce vracející HTML podobu dat konkrétní úlohy
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
   *     description="Task HTML details",
   *     @SWG\Schema(type="xml")
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

    //serializace GUHA PMML
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
   * Akce vracející detaily konkrétní úlohy
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
   * Akce pro zadání nové úlohy...
   *
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
   * Funkce pro validaci zadání nové úlohy
   * @param null|string $id=null (pokud $id=="simple", dojde k přesměrování na funkci validateSimple)
   * @throws NotImplementedException
   */
  public function validateCreate($id=null) {
    if ($id=='simple'){$this->forward('simple');return;}
    //TODO implementovat podporu zadání komplexní úlohy
    throw new NotImplementedException();
  }

  /**
   * Akce pro zadání nové úlohy s jednoduchou konfigurací
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
   * Funkce pro validaci jednoduchého zadání úlohy
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
   * Akce pro spuštění dolování konkrétní úlohy
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

      //spustíme background požadavek na kontrolu stavu úlohy (jestli je dokončená atp.
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
   * Akce pro spuštění dolování konkrétní úlohy
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
   * Akce pro zastavení dolování konkrétní úlohy
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

  #region akce pro periodickou kontrolu dolování a import na pozadí
  /**
   * Akce pro periodickou kontrolu stavu úlohy na serveru
   * @param int $id - ID úlohy, kterou chceme zkontrolovat
   * @param int $timeout = 0 - timestamp, kdy dojde k neúspěšnému ukončení úlohy
   */
  public function actionReadMiningCheckState($id,$timeout=0) {
    //zakážeme ukončení skriptu při zavření přenosového kanálu
    RequestHelper::ignoreUserAbort();
    //nejprve pozastavíme běh skriptu (abychom měli nějakou pauzu mezi jednotlivými kontrolami stavu úlohy)
    sleep(self::MINING_STATE_CHECK_INTERVAL);
    //najdeme úlohu a mining driver
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->currentUser);
    //zkontrolujeme stav vzdálené úlohy a zaktualizujeme ho
    $taskState=$miningDriver->checkTaskState();
    $this->tasksFacade->updateTaskState($task,$taskState);

    #region akce v závislosti na aktuálním běhu úlohy
    if ($taskState->importState==Task::IMPORT_STATE_WAITING){
      //pokud máme na serveru čekající import, zkusíme poslat požadavek na jeho provedení
      $backgroundImportUrl=$this->getAbsoluteLink('readMiningImportResults',['id'=>$id,'relation'=>'miningImportResults'],Link::SELF,true);
      RequestHelper::sendBackgroundGetRequest($backgroundImportUrl);
    }
    if ($taskState->state==Task::STATE_IN_PROGRESS){
      if ($timeout>0 && $timeout<time()){
        //byl nastaven timeout a zároveň tento timeout vypršel => úlohu zrušíme
        $this->forward('readStop',$id);
      }else{
        //odešleme samostatný požadavek na opakované načtení této akce (se všemi stávajícími parametry)
        RequestHelper::sendBackgroundGetRequest($this->getAbsoluteLink('self',[],Link::SELF,true));
      }
    }
    #endregion
    $this->sendTextResponse(time().' DONE '.$this->action);
  }

  /**
   * Akce pro import výsledků
   * @param int
   */
  public function actionReadMiningImportResults($id) {
    //zakážeme ukončení skriptu při zavření přenosového kanálu
    RequestHelper::ignoreUserAbort();
    //najdeme úlohu a mining driver
    $task=$this->findTaskWithCheckAccess($id);
    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->currentUser);

    //import budeme provádět pouze v případě, že zatím neběží jiný import (abychom předešli konfliktům a zahlcení serveru)
    if ($task->importState==Task::IMPORT_STATE_WAITING){
      //označíme částečný probíhající import
      $taskState=$task->getTaskState();
      $taskState->importState=Task::IMPORT_STATE_PARTIAL;
      $this->tasksFacade->updateTaskState($task,$taskState);
      //provedeme import plného PMML (klidně jen částečných výsledků) a zaktualizujeme stav úlohy
      $taskState=$miningDriver->importResultsPMML();
      $this->tasksFacade->updateTaskState($task,$taskState);

      //spustíme další dílší import
      sleep(self::MINING_STATE_CHECK_INTERVAL);//TODO testovací
      RequestHelper::sendBackgroundGetRequest($this->getAbsoluteLink('self',[],Link::SELF,true));
    }

    $this->sendTextResponse(time().' DONE '.$this->action);
  }
  #endregion


  #region actionReadRules
  /**
   * Akce vracející jeden konkrétní ruleset se základním přehledem pravidel
   * @param int $id
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
   *   @SWG\Response(
   *     response=200,
   *     description="List of rules",
   *     @SWG\Schema(
   *       @SWG\Property(property="task",ref="#/definitions/TaskSimpleResponse"),
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
  public function actionReadRules($id){
    $task=$this->findTaskWithCheckAccess($id);
    if ($task->state!=Task::STATE_SOLVED){
      throw new InvalidStateException("Task has not been solved!");
    }
    $result=[
      'task'=>$task->getDataArr(),
      'rules'=>[]
    ];
    if ($task->rulesCount>0){
      /** @var Rule[] $rules */
      $rules=$task->rules;
      if (!empty($rules)){
        foreach($rules as $rule){
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
   * Akce pro zadání nové úlohy s jednoduchou konfigurací
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
    //TODO implementovat
    throw new \Nette\NotImplementedException();
    /* XXX
    $inputData=$this->input->getData();
    $miner=$this->findMinerWithCheckAccess($inputData['miner']);
    $task=$this->tasksFacade->prepareSimpleTask($miner, $inputData);
    $this->tasksFacade->saveTask($task);
    //send task details
    $this->resource=$task->getDataArr(true);
    $this->sendResource();*/
  }

  /**
   * Funkce pro validaci jednoduchého zadání úlohy
   */
  public function validateUpdate() {
    //TODO implementovat
    throw new \Nette\NotImplementedException();
    //XXX
    /*
    $this->input->field('miner')->addRule(IValidator::CALLBACK,'You cannot use the given miner, or the miner has not been found!',function($value) {
      try {
        $miner=$this->findMinerWithCheckAccess($value);
        return true;
      } catch(\Exception $e) {
        return false;
      }
    });
    $this->input->field('name')->addRule(IValidator::REQUIRED,'You have to input the task name!');
    $this->input->field('limitHits')
      ->addRule(IValidator::INTEGER,'Max rules count (limitHits) has to be positive integer!')
      ->addRule(IValidator::RANGE,'Max rules count (limitHits) has to be positive integer!',[1,null]);
    //kontrola strukturovaných vstupů
    $inputData=$this->input->getData();
    $this->input->field('IMs')
      ->addRule(IValidator::REQUIRED,'You have to input interest measure thresholds!')
      ->addRule(IValidator::CALLBACK,'Invalid structure of interest measure thresholds!',function()use($inputData){
        $fieldInputData=$inputData['IMs'];
        if (empty($fieldInputData)){return false;}
        return true;
      });
    $this->input->field('consequent')
      ->addRule(IValidator::REQUIRED,'You have to input interest the structure of consequent!');
    */
  }
  #endregion actionUpdate

  #region actionDelete
  /**
   * Akce pro smazání konkrétní úlohy
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