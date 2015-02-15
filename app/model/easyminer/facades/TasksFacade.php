<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Entities\TaskState;
use App\Model\EasyMiner\Repositories\TasksRepository;

class TasksFacade {
  /** @var  TasksRepository $tasksRepository */
  private $tasksRepository;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  public function __construct(TasksRepository $tasksRepository, RulesFacade $rulesFacade){
    $this->tasksRepository=$tasksRepository;
    $this->rulesFacade=$rulesFacade;
  }

  /**
   * @param $id
   * @return Task
   * @throws \Exception
   */
  public function findTask($id){
    return $this->tasksRepository->find($id);
  }

  /**
   * @param Miner|int $miner
   * @param string $taskUuid
   * @return Task
   * @throws \Exception
   */
  public function findTaskByUuid($miner,$taskUuid){
    return $this->tasksRepository->findBy(array('miner_id'=>(($miner instanceof Miner)?$miner->minerId:$miner),'task_uuid'=>$taskUuid));
  }

  /**
   * @param Task $task
   * @return mixed
   */
  public function saveTask(Task &$task){
    return $this->tasksRepository->persist($task);
  }

  /**
   * @param Task $task
   * @param TaskState $taskState
   */
  public function updateTaskState(Task &$task,TaskState $taskState){
    if (!empty($taskState->rulesCount) && $taskState->rulesCount>$task->rulesCount){
      $task->rulesCount=$taskState->rulesCount;
    }
    if (($task->state!=Task::STATE_SOLVED)&&($task->state!=$taskState->state)){
      $task->state=$taskState->state;
    }
    $task->resultsUrl=$taskState->resultsUrl;

    if ($task->isModified()){
      $this->saveTask($task);
    }
  }

  /**
   * Funkce pro kontrolu, jestli je zvolená úloha obsažená v Rule Clipboard
   * @param Task $task
   */
  public function checkTaskInRuleClipoard(Task &$task){
    $rulesCount=$this->rulesFacade->getRulesCountByTask($task,true);
    if ($rulesCount!=$task->rulesInRuleClipboardCount){
      $task->rulesInRuleClipboardCount=$rulesCount;
      $this->saveTask($task);
    }
  }


  /**
   * Funkce pro uložení úlohy s daným uuid (než se odešle mineru...)
   * @param Miner $miner
   * @param string $taskUuid
   * @return \App\Model\EasyMiner\Entities\Task
   */
  public function prepareTaskWithUuid(Miner $miner,$taskUuid){
    try{
      $task=$this->findTaskByUuid($miner,$taskUuid);
      return $task;
    }catch (\Exception $e){/*úloha pravděpodobně neexistuje...*/}
    $task=new Task();
    $task->taskUuid=$taskUuid;
    $task->miner=$miner;
    $task->type=$miner->type;
    $task->state=Task::STATE_NEW;
    $this->saveTask($task);

    return $task;
  }

}