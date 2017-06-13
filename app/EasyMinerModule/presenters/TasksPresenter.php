<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;
use EasyMinerCenter\Libs\RequestHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class TasksPresenter - presenter for work with rule mining tasks
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class TasksPresenter  extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;
  use UsersTrait;

  /** @var RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var TasksFacade $tasksFacade */
  private $tasksFacade;
  /** @var XmlTransformator $xmlTransformator */
  private $xmlTransformator;
  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;

  /**
   * Action for start of data mining or reading the task state (sends JSON response)
   * @param int|null $id
   * @param string $miner
   * @param string $data
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionStartMining($id=null,$miner,$data){
    //find the minr and check user privileges
    $miner=$this->findMinerWithCheckAccess($miner);
    //find the task or create a new one (if $id=null)
    $task=$this->tasksFacade->prepareTask($miner, $id);

    if ($task->state=='new'){
      #region newly imported task
      //get task name from its config JSON
      $dataArr=Json::decode($data,Json::FORCE_ARRAY);
      //task config
      if (!empty($dataArr['taskName'])){
        $task->name=$dataArr['taskName'];
      }else{
        $task->name='task '.$id;
      }
      $task->taskSettingsJson=$data;
      $this->tasksFacade->saveTask($task);
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->getCurrentUser());
      $taskState=$miningDriver->startMining();
      #endregion newly imported task
    }else{
      #region check state of an existing task
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->getCurrentUser());
      $taskState=$miningDriver->checkTaskState();
      #endregion check state of an existing task
    }

    $this->tasksFacade->updateTaskState($task,$taskState);
    if ($taskState->importState==Task::IMPORT_STATE_WAITING){
      //if we have a waiting import on server, we try to send request to process it
      RequestHelper::sendBackgroundGetRequest($this->getBackgroundImportLink($task));
    }
    $taskState=$task->getTaskState();
    $this->sendJsonResponse($taskState->asArray());
  }


  /**
   * Action for starting of import of results (saved in TEMP folder on the server)
   * @param int $task
   */
  public function actionImportMiningResults($task){
    //load the task
    try {
      $task=$this->tasksFacade->findTask($task);
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$task->miner->user);
    }catch (\Exception $e){
      //the task was not found, so there is nothing to import...
      $this->terminate();
      return;
    }

    //ignore the end of the user request (continue with run in background)
    RequestHelper::ignoreUserAbort();

    //run import only if there is not another import running
    if ($task->importState==Task::IMPORT_STATE_WAITING){
      //mark run of partial import
      $taskState=$task->getTaskState();
      $importData=$taskState->getImportData();
      if (empty($importData)){
        $this->terminate();
      }
      $taskState->importState=Task::IMPORT_STATE_PARTIAL;
      $this->tasksFacade->updateTaskState($task,$taskState);
      //run the import of full PMML and update the task state
      $taskState=$miningDriver->importResultsPMML();
      $this->tasksFacade->updateTaskState($task,$taskState);

      //run next import request (next partial results)
      RequestHelper::sendBackgroundGetRequest($this->getBackgroundImportLink($task));
    }
    $this->terminate();
  }

  /**
   * Action for stopping of rule mining
   * @param int $id
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionStopMining($id){
    //find the miner and check user privileges
    $task=$this->tasksFacade->findTask($id);

    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $miningDriver=$this->minersFacade->getTaskMiningDriver($task,$this->getCurrentUser());
    $taskState=$miningDriver->stopMining();
    $this->tasksFacade->updateTaskState($task,$taskState);

    $this->sendJsonResponse($taskState->asArray());
  }

  /**
   * Action returning rules for EasyMiner-MiningUI
   * @param int $id
   * @param $offset
   * @param $limit
   * @param $order
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionGetRules($id,$offset=0,$limit=25,$order='rule_id'){
    if ($order==''){
      $order='rule_id';
    }

    //find task and miner and check user privileges
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit);

    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('task'=>array('name'=>$task->name,'rulesCount'=>$task->rulesCount,'IMs'=>$task->getInterestMeasures(),'state'=>$task->state,'importState'=>$task->importState),'rules'=>$rulesArr));
  }

  /**
   * Action for renaming of the task
   * @param int $id
   * @param string $name
   * @throws \Exception
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionRenameTask($id,$name){
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;

    $this->checkMinerAccess($miner);
    $task->name=$name;
    if (!$this->tasksFacade->saveTask($task)){
      throw new \Exception($this->translator->translate('Task rename failed!'));
    }
    $this->sendJsonResponse(array('state'=>'ok'));
  }

  /**
   * Action for changing of rule order (in DB)
   * @param int $id = null
   * @param string $rulesOrder
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   * @throws \Exception
   */
  public function actionRulesOrder($id,$rulesOrder=''){
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    if ($rulesOrder!=''){
      //there is new rule order...
      try{
        $task->setRulesOrder($rulesOrder);
      }catch (\Exception $e){
        throw new \Exception($this->translator->translate('Rules order was not saved!'));
      }
    }
    $this->sendJsonResponse(['task'=>$task->taskId,'rulesOrder'=>$rulesOrder]);
  }

  /**
   * Action for rendering of task details in PMML
   * @param int $id
   * @throws \Exception
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderTaskPMML($id){
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    //prepare and send the PMML content
    $pmml=$this->prepareTaskPmml($task);
    $this->sendXmlResponse($pmml);
  }

  /**
   * Action for rendering of task config in PMML
   * @param int $id
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderTaskSettingPMML($id) {
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $pmml=$this->prepareTaskSettingPmml($task);
    $this->sendXmlResponse($pmml);
  }

  /**
   * Akce pro vykreslení detailů úlohy ve formátu AssociationRulesXML
   * Action for rendering of task details in
   * @param int $id
   * @throws \Exception
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderTaskXML($id){
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    //prepare and send XML response
    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($task->rules);
    $this->sendXmlResponse($associationRulesXmlSerializer->getXml());
  }

  /**
   * Action for rendering of task rules in DRL
   * @param int $id
   * @throws \Exception
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderTaskDRL($id){
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($task->rules);
    $xml=$associationRulesXmlSerializer->getXml();
    $this->sendTextResponse($this->xmlTransformator->transformToDrl($xml));
  }

  /**
   * Action for rendering task details as HTML
   * @param int $id
   * @throws \Exception
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderTaskDetails($id){
    $task=$this->tasksFacade->findTask($id);
    $this->checkMinerAccess($task->miner);

    //generate PMML and transform it to HTML
    $pmml=$this->prepareTaskPmml($task);
    $this->template->task=$task;
    $this->template->content=$this->xmlTransformator->transformToHtml($pmml);
  }

  /**
   * Private method for initilization of PmmlSerializer based on task config
   * @param Task $task
   * @param bool $includeFrequencies=true
   * @return GuhaPmmlSerializer
   */
  private function initPmmlSerializer(Task $task, $includeFrequencies=true) {
    $pmmlSerializer=$this->xmlSerializersFactory->createGuhaPmmlSerializer($task,null);
    $pmmlSerializer->appendTaskSettings();
    $pmmlSerializer->appendDataDictionary($includeFrequencies);
    $pmmlSerializer->appendTransformationDictionary($includeFrequencies);
    return $pmmlSerializer;
  }

  /**
   * Private method returning initialized PmmlSerializer
   * @param Task $task
   * @return \SimpleXMLElement
   */
  private function prepareTaskSettingPmml(Task $task) {
    $pmmlSerializer=$this->initPmmlSerializer($task, false);
    return $pmmlSerializer->getPmml();
  }

  /**
   * Private method returning complete PMML export of a task
   * @param Task $task
   * @return \SimpleXMLElement
   */
  private function prepareTaskPmml(Task $task){
    $pmmlSerializer=$this->initPmmlSerializer($task, true);
    $pmmlSerializer->appendRules();
    return $pmmlSerializer->getPmml();
  }

  /**
   * Private method returning relative URL for import of data mining results (for run in background)
   * @param Task $task
   * @return string
   */
  private function getBackgroundImportLink(Task $task){
    $this->absoluteUrls=true;
    $link=$this->link('importMiningResults',['task'=>$task->taskId]);
    if (Strings::startsWith($link,'/')){
      $link=rtrim($this->getHttpRequest()->getUrl()->getHostUrl(),'/').$link;
    }
    return $link;
  }

  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade) {
    $this->rulesFacade = $rulesFacade;
  }

  /**
   * @param TasksFacade $tasksFacade
   */
  public function injectTasksFacade(TasksFacade $tasksFacade){
    $this->tasksFacade=$tasksFacade;
  }

  /**
   * @param XmlTransformator $xmlTransformator
   */
  public function injectXmlTransformator(XmlTransformator $xmlTransformator){
    $this->xmlTransformator=$xmlTransformator;
    //nastaven basePath
    $this->xmlTransformator->setBasePath($this->template->basePath);
  }

  /**
   * @param XmlSerializersFactory $xmlSerializersFactory
   */
  public function injectXmlSerializersFactory(XmlSerializersFactory $xmlSerializersFactory) {
    $this->xmlSerializersFactory=$xmlSerializersFactory;
  }
  #endregion
} 