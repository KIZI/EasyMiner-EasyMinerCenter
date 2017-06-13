<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;

/**
 * Trait TasksFacadeTrait
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @method User getCurrentUser()
 * @method error($message = null, $code = 404)
 */
trait TasksFacadeTrait {
  use MinersFacadeTrait;

  /** @var  TasksFacade $tasksFacade */
  protected $tasksFacade;

  /**
   * Method for finding a Task by $taskId, also checks the user privileges to work with the found task
   * @param int $taskId
   * @return Task
   * @throws \Nette\Application\BadRequestException
   */
  protected function findTaskWithCheckAccess($taskId){
    try{
      /** @var Task $task */
      $task=$this->tasksFacade->findTask($taskId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested task was not found.');
      return null;
    }
    $this->minersFacade->checkMinerAccess($task->miner,$this->getCurrentUser());
    return $task;
  }

  #region injections
  /**
   * @param TasksFacade $tasksFacade
   */
  public function injectTasksFacade(TasksFacade $tasksFacade){
    $this->tasksFacade=$tasksFacade;
  }
  #endregion injections
}