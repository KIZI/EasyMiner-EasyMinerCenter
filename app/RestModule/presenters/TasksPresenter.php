<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\InvalidStateException;
use Drahak\Restful\NotImplementedException;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializerFactory;
use Nette\Application\BadRequestException;

/**
 * Class TasksPresenter - presenter pro práci s jednotlivými úlohami
 * @package EasyMinerCenter\RestModule\Presenters
 */
class TasksPresenter extends BaseResourcePresenter {
  use TasksFacadeTrait;
  /** @const MINING_STATE_CHECK_INTERVAL - doba čekání mezi kontrolami stavu úlohy */
  const MINING_STATE_CHECK_INTERVAL=1000;

  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var  GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory */
  private $guhaPmmlSerializerFactory;

  #region actionReadPmml
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

    $pmml=$this->prepareTaskPmml($task);
    $this->sendXmlResponse($pmml);
  }

  /**
   * @param $task
   * @return \SimpleXMLElement
   */
  private function prepareTaskPmml(Task $task){
    /** @var Metasource $metasource */
    $metasource=$task->miner->metasource;
    $this->databasesFacade->openDatabase($metasource->getDbConnection());
    $pmmlSerializer=$this->guhaPmmlSerializerFactory->create($task,null,$this->databasesFacade,true);
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
   * @throws NotImplementedException
   */
  public function actionCreate($id=null) {
    //FIXME implement
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
      ->addRule(IValidator::CALLBACK,'You have to input interest measure thresholds!',function($value){
        return count($value)>0;
      });
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
      //send task simple details
      $this->resource=$task->getDataArr(false);
      $this->sendResource();
    }else{
      $this->redirect('readState',['id'=>$id]);
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
   */
  public function actionReadMiningCheckState($id) {
    //TODO implement
  }

  /**
   * Akce pro import výsledků
   * @param int
   */
  public function actionReadMiningImportResults() {
    //TODO
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
   * @SWG\Put(
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
    //XXX
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
  public function validateUpdate() {
    //TODO implementovat
    throw new \Nette\NotImplementedException();
    //XXX
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
  }
  #endregion actionUpdate


  #region injections
  /**
   * @param DatabasesFacade $databasesFacade
   */
  public function injectDatabasesFacade(DatabasesFacade $databasesFacade) {
    $this->databasesFacade=$databasesFacade;
  }
  /**
   * @param GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory
   */
  public function injectGuhaPmmlSerializerFactory(GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory) {
    $this->guhaPmmlSerializerFactory=$guhaPmmlSerializerFactory;
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