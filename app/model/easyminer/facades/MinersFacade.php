<?php
namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\MinersRepository;

class MinersFacade {
  /** @var  MinersRepository $minersRepository */
  private $minersRepository;

  public function __construct(MinersRepository $minersRepository) {
    $this->minersRepository = $minersRepository;
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
      $miner=$this->minersRepository->findBy(array('miner_id'=>$id,'user_id'=>$user));
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

}
