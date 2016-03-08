<?php
namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\MinersRepository;
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
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  MiningDriverFactory $miningDriverFactory */
  private $miningDriverFactory;

  public function __construct(MiningDriverFactory $miningDriverFactory,MinersRepository $minersRepository, MetasourcesFacade $metasourcesFacade, RulesFacade $rulesFacade, TasksFacade $tasksFacade, MetaAttributesFacade $metaAttributesFacade){
    $this->minersRepository = $minersRepository;
    $this->metasourcesFacade=$metasourcesFacade;
    $this->miningDriverFactory=$miningDriverFactory;
    $this->rulesFacade=$rulesFacade;
    $this->tasksFacade=$tasksFacade;
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
    $minerId=$this->minersRepository->persist($miner);
    $miner=$this->findMiner($minerId);
    if (empty($miner->metasource)){
      $miner->metasource=$this->metasourcesFacade->initMetasourceForMiner($miner);
      $this->saveMiner($miner);
    }
    return $minerId;
  }

  /**
   * @param Miner|int $miner
   * @param User $user;
   * @return int
   */
  public function deleteMiner($miner, User $user){
    if (!($miner instanceof Miner)){
      $miner=$this->findMiner($miner);
    }
    #region smazání všech navázaných instancí driverů
    //u samotného driveru
    $task=new Task();
    $task->miner=$miner;
    $miningDriver=$this->miningDriverFactory->getDriverInstance($task,$this,$this->rulesFacade,$this->metaAttributesFacade,$user);
    $miningDriver->deleteMiner();
    //u jednotlivých úloh
    $tasks=$miner->tasks;
    if (!empty($tasks)){
      foreach ($tasks as $task){
        $miningDriver=$this->miningDriverFactory->getDriverInstance($task,$this,$this->rulesFacade,$this->metaAttributesFacade,$user);
        $miningDriver->deleteMiner();
      }
    }
    #endregion
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
    //TODO musí tu tato kontrola vůbec být?
    if (empty($miner->metasource)){
      $this->saveMiner($miner);
    }
  }

  /**
   * @param Miner|int $miner
   * @param Attribute|int $attribute
   */
  public function prepareAttribute($miner,$attribute){
    if (!$miner instanceof Miner){
      /*$miner=*/$this->findMiner($miner);//kontrola existence daného mineru
    }
    if ($attribute instanceof Attribute){
      if ($attribute->isDetached() || $attribute->isModified()){
        $this->metasourcesFacade->saveAttribute($attribute);
      }
    }else{
      $attribute=$this->metasourcesFacade->findAttribute($attribute);
    }
    $this->preprocessingDriver->generateAttribute($attribute);
    //TODO nechat mining driver zkontrolovat existenci všech atributů
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
   * @return \EasyMinerCenter\Model\Mining\IMiningDriver
   */
  public function getTaskMiningDriver($task, User $user){
    if (!$task instanceof Task){
      $task=$this->tasksFacade->findTask($task);
    }
    return $this->miningDriverFactory->getDriverInstance($task,$this,$this->rulesFacade,$this->metaAttributesFacade,$user);
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
   * @return array
   */
  public function getAvailableMinerTypes() {
    $minerTypes=Miner::getTypes();
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
