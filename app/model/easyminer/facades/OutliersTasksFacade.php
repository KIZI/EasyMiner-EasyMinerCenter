<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTaskState;
use EasyMinerCenter\Model\EasyMiner\Repositories\OutliersTasksRepository;

/**
 * Class OutliersTasksFacade - facade for work with OutliersTasks
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class OutliersTasksFacade {
  /** @var  OutliersTasksRepository $outliersTasksRepository */
  private $outliersTasksRepository;

  public function __construct(OutliersTasksRepository $outliersTasksRepository){
    $this->outliersTasksRepository=$outliersTasksRepository;
  }

  /**
   * Method for finding of an OutliersTask by the OutliersTaskId
   * @param $id
   * @return OutliersTask
   * @throws \Exception
   */
  public function findOutliersTask($id){
    return $this->outliersTasksRepository->find($id);
  }

  /**
   * Method for finding of an OutliersTask with given miner ID and minSupport
   * @param Miner|int $miner
   * @param float $minSupport
   * @return OutliersTask
   * @throws EntityNotFoundException
   */
  public function findOutliersTaskByParams($miner, $minSupport){
    if ($miner instanceof Miner){
      $miner=$miner->minerId;
    }
    return $this->outliersTasksRepository->findBy(['miner_id'=>$miner,'min_support'=>$minSupport]);
  }

  /**
   * Method for saving of OutlietsTask
   * @param OutliersTask $outliersTask
   * @return mixed
   */
  public function saveOutliersTask(OutliersTask &$outliersTask){
    return $this->outliersTasksRepository->persist($outliersTask);
  }

  /**
   * Method for deleting of OutliersTask
   * @param OutliersTask $outliersTask
   * @return bool
   */
  public function deleteOutliersTask(OutliersTask $outliersTask){
    try{
      $this->outliersTasksRepository->delete($outliersTask);
    }catch(\Exception $e){
      return false;
    }
    return true;
  }

  /**
   * Method for updating of OutliersTask state
   * @param OutliersTask $outliersTask
   * @param OutliersTaskState $outliersTaskState
   */
  public function updateTaskState(OutliersTask &$outliersTask,OutliersTaskState $outliersTaskState){
    /** @var OutliersTask $task - aktualizujeme data o konkrétní úloze*/
    $outliersTask=$this->findOutliersTask($outliersTask->outliersTaskId);

    //task solving state
    if (($outliersTask->state!=OutliersTask::STATE_SOLVED)&&($outliersTask->state!=$outliersTaskState->state)){
      $outliersTask->state=$outliersTaskState->state;
    }

    //URL with results
    $outliersTask->resultsUrl=(!empty($outliersTaskState->resultsUrl)?$outliersTaskState->resultsUrl:'');

    //ID of remote miner task
    $outliersTask->minerOutliersTaskId=$outliersTaskState->minerOutliersTaskId;

    if ($outliersTask->isModified()){
      $this->saveOutliersTask($outliersTask);
    }
  }

}