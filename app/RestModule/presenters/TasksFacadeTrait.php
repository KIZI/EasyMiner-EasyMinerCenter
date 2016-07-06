<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;

/**
 * Trait TasksFacadeTrait
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\RestModule\Presenters
 *
 * @method User getCurrentUser()
 * @method error($message = null, $code = 404)
 */
trait TasksFacadeTrait {
  use MinersFacadeTrait;

  /** @var  TasksFacade $tasksFacade */
  protected $tasksFacade;



  /**
   * Funkce pro nalezení úlohy dle zadaného ID a kontrolu oprávnění aktuálního uživatele pracovat s daným pravidlem
   *
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