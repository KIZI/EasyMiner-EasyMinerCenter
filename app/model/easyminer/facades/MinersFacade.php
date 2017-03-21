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
   * Funkce pro nalezení všech minerů vycházejících z konkrétního
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
   * Funkce pro nalezení všech minerů vycházejících z konkrétního
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
   * Funkce pro kontrolu, jestli je uživatel vlastníkem daného mineru
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
    }catch (\Exception $e){/*chybu ignorujeme*/}
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
   * Funkce pro smazání mineru včetně všech navázaných úloh
   *
   * @param Miner $miner
   * @return int
   */
  public function deleteMiner(Miner $miner){
    #region smazání navázaných úloha a navázané instance driveru
    $tasks=$miner->tasks;
    if (!empty($tasks)){
      //smazání jednotlivých úloh
      foreach ($tasks as $task){
        $this->tasksFacade->deleteTask($task);
      }
    }
    //smazání mineru jako takového
    $miningDriver=$this->miningDriverFactory->getDriverInstance(null,$this,$this->rulesFacade,$this->metaAttributesFacade,$miner->user);
    $miningDriver->deleteMiner();
    #endregion smazání navázaných úloha a navázané instance driveru
    #region smazání metasource
    if (!empty($miner->metasource)){
      if (count($this->findMinersByMetasource($miner->metasource))<=1){
        //kontrola, jestli daný metasource využívá větší množství minerů - pokud ne, odstraníme i metasource
        $this->metasourcesFacade->deleteMetasource($miner->metasource);
      }
    }
    #endregion smazání metasource
    return $this->minersRepository->delete($miner);
  }

  /**
   * Funkce pro kontrolu, jestli má miner správně nakonfigurovanou metabázi
   * @param $miner
   */
  public function checkMinerMetasource($miner){
    if (!$miner instanceof Miner){
      $miner=$this->findMiner($miner);
    }
    if (empty($miner->metasource)){
      //kontrola, jestli má minet vytvořené metasource
      $this->saveMiner($miner);
    }
  }

  /**
   * Funkce pro připravení nového názvu atributu (takového, který se zatím v seznamu atributů nevyskytuje)
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
   * Funkce vracející driver k mineru pro dolování outlierů
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
   * Funkce pro kontrolu stavu konkrétního mineru (jestli jsou nadefinované všechny atributy atd.
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
   * Funkce vracející pole s identifikací dostupných minerů
   * @param string $datasourceType - typ databáze, ke které se vztahují dané minery
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
