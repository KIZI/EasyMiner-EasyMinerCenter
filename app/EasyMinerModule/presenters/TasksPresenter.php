<?php

namespace App\EasyMinerModule\Presenters;

/**
 * Class TasksPresenter - presenter pro práci s úlohami...
 * @package App\EasyMinerModule\Presenters
 */
class TasksPresenter  extends BasePresenter{

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
    $this->sendJsonResponse($task->taskState);
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
   * @param $task
   * @param $start
   * @param $count
   */
  public function actionGetRules($task,$start,$count){
    //TODO akce pro vrácení části výsledků
  }


} 