<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Repositories\OutliersTasksRepository;

/**
 * Class OutliersTasksFacade - fasáda pro práci s OutliersTasks
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 */
class OutliersTasksFacade {
  /** @var  OutliersTasksRepository $outliersTasksRepository */
  private $outliersTasksRepository;

  public function __construct(OutliersTasksRepository $outliersTasksRepository){
    $this->outliersTasksRepository=$outliersTasksRepository;
  }

  /**
   * @param $id
   * @return OutliersTask
   * @throws \Exception
   */
  public function findOutliersTask($id){
    return $this->outliersTasksRepository->find($id);
  }

  /**
   * Funkce pro nalezení outliersTask na základě ID úlohy a minSupportu
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
   * @param OutliersTask $outliersTask
   * @return mixed
   */
  public function saveOutliersTask(OutliersTask &$outliersTask){
    return $this->outliersTasksRepository->persist($outliersTask);
  }

  /**
   * Funkce pro smazání DM úlohy včetně připojených pravidel
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

}