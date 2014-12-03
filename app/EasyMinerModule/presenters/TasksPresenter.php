<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\Mining\LM\LMDriver;

/**
 * Class TasksPresenter - presenter pro práci s úlohami...
 * @package App\EasyMinerModule\Presenters
 */
class TasksPresenter  extends BasePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

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
    $task=$this->minersFacade->prepareTaskWithUuid($miner,$taskUuid);
    if ($task->state=='new'){
      #region nově importovaná úloha
      //konfigurace úlohy
      $task->name=$taskUuid;//TODO načtení info o názvu úlohy
      $task->taskSettingsJson=$data;
      $this->minersFacade->saveTask($task);
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
      $taskState=$miningDriver->startMining();
      #endregion
    }else{
      #region zjištění stavu již existující úlohy
      $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
      $taskState=$miningDriver->checkTaskState();
      #endregion
    }
    $this->minersFacade->updateTaskState($task,$taskState);
    $this->sendJsonResponse($task->getTaskState()->asArray());
  }

  /**
   * Akce pro zastavení dolování
   * @param string $miner
   * @param string $task
   */
  public function actionStopMining($miner,$task){
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->minersFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $miningDriver=$this->minersFacade->getTaskMiningDriver($task);
    $taskState=$miningDriver->stopMining();

    $this->minersFacade->updateTaskState($task,$taskState);

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
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->minersFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit);

    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=array('text'=>$rule->text,'confidence'=>$rule->confidence,'support'=>$rule->support,'lift'=>$rule->lift,
          'a'=>$rule->a,'b'=>$rule->b,'c'=>$rule->c,'d'=>$rule->d,'selected'=>($rule->inRuleClipboard?'1':'0'));
      }
    }
    $this->sendJsonResponse(array('task'=>array('rulesCount'=>$task->rulesCount),'rules'=>$rulesArr));
  }


  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  #endregion
} 