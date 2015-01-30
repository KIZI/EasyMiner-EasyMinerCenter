<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\EasyMiner\Facades\TasksFacade;
use Nette\Utils\Json;

/**
 * Class TasksPresenter - presenter pro práci s úlohami...
 * @package App\EasyMinerModule\Presenters
 */
class TasksPresenter  extends BasePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  TasksFacade $tasksFacade */
  private $tasksFacade;

  /**
   * Akce pro spuštění dolování či zjištění stavu úlohy (vrací JSON)
   * @param string $miner
   * @param string $task
   * @param string $data
   */
  public function actionStartMining($miner,$task,$data){
    $taskUuid=$task;
    /****************************************************************************************************************/
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $miner=$this->minersFacade->findMiner($miner);
    $this->checkMinerAccess($miner);
    //nalezení či připravení úlohy...
    $task=$this->tasksFacade->prepareTaskWithUuid($miner,$taskUuid);
    if ($task->state=='new'){
      #region nově importovaná úloha
      //zjištění názvu úlohy z jsonu s nastaveními
      $dataArr=Json::decode($data,Json::FORCE_ARRAY);
      //konfigurace úlohy
      if (!empty($dataArr['taskName'])){
        $task->name=$dataArr['taskName'];
      }else{
        $task->name=$taskUuid;
      }
      $task->taskSettingsJson=$data;
      $this->tasksFacade->saveTask($task);
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
      $taskState=$miningDriver->startMining();
      #endregion
    }else{
      #region zjištění stavu již existující úlohy
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
      $taskState=$miningDriver->checkTaskState();
      #endregion
    }
    $this->tasksFacade->updateTaskState($task,$taskState);
    $this->sendJsonResponse($task->getTaskState()->asArray());
  }

  /**
   * Akce pro zastavení dolování
   * @param string $miner
   * @param string $task
   */
  public function actionStopMining($miner,$task){
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);


    $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
    $taskState=$miningDriver->stopMining();

    $this->tasksFacade->updateTaskState($task,$taskState);

    $this->sendJsonResponse($taskState->asArray());
  }

  /**
   * Akce vracející pravidla pro vykreslení v easymineru
   * @param $miner
   * @param $task
   * @param $offset
   * @param $limit
   * @param $order
   */
  public function actionGetRules($miner,$task,$offset=0,$limit=25,$order='rule_id'){
    if ($order==''){
      $order='rule_id';
    }
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit);

    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('task'=>array('name'=>$task->name,'rulesCount'=>$task->rulesCount,'IMs'=>$task->getInterestMeasures()),'rules'=>$rulesArr));
  }

  /**
   * Akce pro přejmenování úlohy v DB
   * @param int $miner
   * @param string $task
   * @param string $name
   * @throws \Nette\Application\ForbiddenRequestException
   * @throws \Exception
   */
  public function actionRenameTask($miner,$task,$name){
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);
    $task->name=$name;
    if (!$this->tasksFacade->saveTask($task)){
      throw new \Exception($this->translator->translate('Task rename failed!'));
    }
    $this->sendJsonResponse(array('state'=>'ok'));
  }

  /**
   * Akce pro vykreslení detailů úlohy ve formátu PMML
   * @param $miner
   * @param $task
   */
  public function renderTaskPMML($miner,$task){
    //TODO akce pro vykreslení detailů úlohy v podobě PMML

  }




  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade) {
    $this->rulesFacade = $rulesFacade;
  }

  public function injectTasksFacade(TasksFacade $tasksFacade){
    $this->tasksFacade=$tasksFacade;
  }
  #endregion
} 