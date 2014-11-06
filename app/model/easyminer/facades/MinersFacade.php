<?php
namespace App\Model\EasyMiner\Facades;

use App\EasyMinerModule\Presenters\TasksPresenter;
use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\Metasource;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\MinersRepository;
use App\Model\EasyMiner\Repositories\TasksRepository;
use App\Model\Mining\MiningDriverFactory;
use App\Model\Preprocessing\IPreprocessingDriver;

class MinersFacade {
  /** @var  MinersRepository $minersRepository */
  private $minersRepository;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  IPreprocessingDriver $preprocessingDriver */
  private $preprocessingDriver;
  /** @var  TasksRepository $tasksRepository */
  private $tasksRepository;
  /** @var  string|MiningDriverFactory $miningDriverFactory */
  private $miningDriverFactory;

  public function __construct($miningDriverFactory,MinersRepository $minersRepository, MetasourcesFacade $metasourcesFacade,IPreprocessingDriver $preprocessingDriver, TasksRepository $tasksRepository){
    $this->minersRepository = $minersRepository;
    $this->metasourcesFacade=$metasourcesFacade;
    $this->preprocessingDriver=$preprocessingDriver;
    $this->tasksRepository=$tasksRepository;
  }

  /**
   * @param int $id
   * @return Miner
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
   * @return bool
   */
  public function saveMiner(Miner $miner){
    return $this->minersRepository->persist($miner);
  }

  /**
   * @param Miner|int $miner
   * @return int
   */
  public function deleteMiner($miner){
    if (!($miner instanceof Miner)){
      $miner=$this->findMiner($miner);
    }
    return $this->minersRepository->delete($miner);
  }

  /**
   * Funkce pro kontrolu, jestli má miner správně nakonfigurovanou metabázi
   * @param $miner
   */
  public function checkMinerMetasource($miner){
    if (!($miner instanceof Miner)){
      $miner=$this->findMiner($miner);
    }
    try{
      $metasource=$miner->metasource;

    }catch (\Exception $e){}
    if (empty($metasource) || (!$metasource instanceof Metasource)){
      $datasource=$miner->datasource;

      $metasource=new Metasource();
      $metasource->miner=$miner;
      $metasource->user=$datasource->user;
      $metasource->dbName=$datasource->dbName;
      $metasource->dbUsername=$datasource->dbUsername;
      $metasource->dbPort=$datasource->dbPort;
      $metasource->type=$datasource->type;
      $metasource->dbServer=$datasource->dbServer;
      $metasource->setDbPassword($datasource->getDbPassword());
      $metasource->attributesTable=$miner->getAttributesTableName();

      $this->metasourcesFacade->createMetasourcesTables($metasource);

      $this->metasourcesFacade->saveMetasource($metasource);

      $miner->metasource=$metasource;
      $this->saveMiner($miner);
    }
  }

  /**
   * @param Miner|int $miner
   * @param Attribute|int $attribute
   */
  public function prepareAttribute($miner,$attribute){
    if (!$miner instanceof Miner){
      $miner=$this->findMiner($miner);
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
   * @param $id
   * @return mixed
   * @throws \Exception
   */
  public function findTask($id){
    return $this->tasksRepository->find($id);
  }

  /**
   * @param Task|int $task
   * @return \App\Model\Mining\IMiningDriver
   */
  public function getTaskMiningDriver($task){
    if (!$task instanceof Task){
      $task=$this->findTask($task);
    }
    $miningDriverFactory=$this->miningDriverFactory;
    return $miningDriverFactory::getDriverInstance($task);
  }


}
