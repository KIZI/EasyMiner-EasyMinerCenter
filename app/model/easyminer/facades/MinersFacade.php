<?php
namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\MinersRepository;
use EasyMinerCenter\Model\Mining\IMiningDriver;
use EasyMinerCenter\Model\Mining\IOutliersMiningDriver;
use EasyMinerCenter\Model\Mining\MiningDriverFactory;

/**
 * Class MinersFacade
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MinersFacade {
  /** @var  MinersRepository $minersRepository */
  private $minersRepository;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  TasksFacade $tasksFacade */
  private $tasksFacade;
  /** @var  OutliersTasksFacade $outliersTasksFacade */
  private $outliersTasksFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  MiningDriverFactory $miningDriverFactory */
  private $miningDriverFactory;

  /**
   * MinersFacade constructor.
   * @param MiningDriverFactory $miningDriverFactory
   * @param MinersRepository $minersRepository
   * @param MetasourcesFacade $metasourcesFacade
   * @param RulesFacade $rulesFacade
   * @param TasksFacade $tasksFacade
   * @param OutliersTasksFacade $outliersTasksFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function __construct(MiningDriverFactory $miningDriverFactory,MinersRepository $minersRepository, MetasourcesFacade $metasourcesFacade, RulesFacade $rulesFacade, TasksFacade $tasksFacade, OutliersTasksFacade $outliersTasksFacade, MetaAttributesFacade $metaAttributesFacade){
    $this->minersRepository = $minersRepository;
    $this->metasourcesFacade=$metasourcesFacade;
    $this->miningDriverFactory=$miningDriverFactory;
    $this->rulesFacade=$rulesFacade;
    $this->tasksFacade=$tasksFacade;
    $this->outliersTasksFacade=$outliersTasksFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
  }

  /**
   * @param int $id
   * @return Miner
   * @throws \Exception
   */
  public function findMiner($id){
    return $this->minersRepository->find($id);
  }

  /**
   * @param int|User $user
   * @return Miner[]|null
   */
  public function findMinersByUser($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $this->minersRepository->findAllBy(array('user_id'=>$user));
  }

  /**
   * Method for finding all miners based on given datasource
   * @param int|Datasource $datasource
   * @return Miner[]|null
   */
  public function findMinersByDatasource($datasource){
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->minersRepository->findAllBy(array('datasource_id'=>$datasource));
  }

  /**
   * Method for finding all miners based on given metasource
   * @param int|Metasource $metasource
   * @return Miner[]|null
   */
  public function findMinersByMetasource($metasource){
    if ($metasource instanceof Metasource){
      $metasource=$metasource->metasourceId;
    }
    return $this->minersRepository->findAllBy(array('metasource_id'=>$metasource));
  }

  /**
   * Method for check, if the user is owner of the given miner
   * @param Miner|int $miner
   * @param User|int $user
   * @return bool
   */
  public function checkMinerAccess($miner,$user){
    if ($miner instanceof Miner){
      $miner=$miner->minerId;
    }
    if ($user instanceof User){
      $user=$user->userId;
    }
    try{
      /** @noinspection PhpUnusedLocalVariableInspection
       * @var Miner $miner
       */
      $miner=$this->minersRepository->findBy(array('miner_id'=>$miner,'user_id'=>$user));
      return true;
    }catch (\Exception $e){/*ignore the error...*/}
    return false;
  }

  /**
   * @param User|int $user
   * @param string $name
   * @return Miner
   * @throws \Exception
   */
  public function findMinerByName($user, $name) {
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $this->minersRepository->findBy(array('name'=>$name,'user_id'=>$user));
  }


  /**
   * @param Miner $miner
   * @return int
   */
  public function saveMiner(Miner &$miner){
    $result=$this->minersRepository->persist($miner);
    if (empty($miner->minerId)){$miner->minerId=$result;}
    $miner=$this->findMiner($miner->minerId);
    return $result;
  }

  /**
   * Method for deleting miner with all its tasks
   * @param Miner $miner
   * @return int
   */
  public function deleteMiner(Miner $miner){
    #region delete connected tasks and miner instances
    $tasks=$miner->tasks;
    if (!empty($tasks)){
      //delete individual tasks
      foreach ($tasks as $task){
        $this->tasksFacade->deleteTask($task);
      }
    }
    //delete miner
    $miningDriver=$this->miningDriverFactory->getDriverInstance(null,$this,$this->rulesFacade,$this->metaAttributesFacade,$miner->user);
    $miningDriver->deleteMiner();
    #endregion delete connected tasks and miner instances
    #region delete metasource
    if (!empty($miner->metasource)){
      if (count($this->findMinersByMetasource($miner->metasource))<=1){
        //check, if the given metasource is used by more miners - if not, delete the metasource
        $this->metasourcesFacade->deleteMetasource($miner->metasource);
      }
    }
    #endregion delete metasource
    return $this->minersRepository->delete($miner);
  }

  /**
   * Method for checking, if the miner has already configured metasource
   * @param $miner
   */
  public function checkMinerMetasource($miner){
    if (!$miner instanceof Miner){
      $miner=$this->findMiner($miner);
    }
    if (empty($miner->metasource)){
      //check the existence of metasource
      $this->saveMiner($miner);
    }
  }

  /**
   * Method for preparing new attribute name (not already existing in list of attributes)
   * @param $miner
   * @param $newAttributeName
   * @return string
   */
  public function prepareNewAttributeName($miner,$newAttributeName) {
    $existingAttributeNames=[];
    $attributes=$miner->metasource->attributes;
    if (!empty($attributes)){
      foreach($attributes as $attribute){
        $existingAttributeNames[]=$attribute->name;
      }
    }
    $newAttributeNameBase=StringsHelper::prepareSafeName($newAttributeName);
    $newAttributeName=$newAttributeNameBase;
    $i=2;
    while(in_array($newAttributeName,$existingAttributeNames)){
      $newAttributeName=$newAttributeNameBase.'_'.$i;
      $i++;
    }
    return $newAttributeName;
  }

  /**
   * Method returning instance of mining driver for the given task
   * @param Task|int $task
   * @param User $user
   * @return IMiningDriver
   */
  public function getTaskMiningDriver($task, User $user){
    if (!($task instanceof Task)){
      $task=$this->tasksFacade->findTask($task);
    }
    return $this->miningDriverFactory->getDriverInstance($task,$this,$this->rulesFacade,$this->metaAttributesFacade,$user);
  }

  /**
   * Method returning instance of outlier mining driver for given outliers task
   * @param OutliersTask|int $outliersTask
   * @param User $user
   * @return IOutliersMiningDriver
   */
  public function getOutliersTaskMiningDriver($outliersTask, User $user){
    if (!($outliersTask instanceof OutliersTask)){
      $outliersTask=$this->outliersTasksFacade->findOutliersTask($outliersTask);
    }
    return $this->miningDriverFactory->getOutlierDriverInstance($outliersTask,$this,$this->metaAttributesFacade,$user);
  }

  /**
   * Method for checking the state of concrete miner (existence of all attributes etc.)
   * @param Miner|int $miner
   * @param User $user
   */
  public function checkMinerState($miner, User $user){
    if (!$miner instanceof Miner){
      $miner=$this->findMiner($miner);
    }
    $task=new Task();
    $task->type=$miner->type;
    $task->miner=$miner;
    $miningDriver=$this->getTaskMiningDriver($task, $user);
    $miningDriver->checkMinerState($user);
  }

  /**
   * Method returning array with identification of available miner types for the given datasource type
   * @param string $datasourceType
   * @return array
   */
  public function getAvailableMinerTypes($datasourceType = null) {
    $minerTypes=Miner::getTypes($datasourceType);
    $resultArr=[];
    if (!empty($minerTypes)){
      foreach($minerTypes as $minerType=>$minerTypeName){
        if ($this->miningDriverFactory->getMinerUrl($minerType)!=''){
          $resultArr[$minerType]=$minerTypeName;
        }
      }
    }
    return $resultArr;
  }
}
