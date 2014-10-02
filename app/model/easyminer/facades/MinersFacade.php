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
      $user=$user->idUser;
    }
    return $this->minersRepository->findAllBy(array('id_user'=>$user));
  }

  /**
   * @param User|int $user
   * @param string $name
   * @return Miner|null
   */
  public function findMinerByName($user, $name) {
    if ($user instanceof User){
      $user=$user->idUser;
    }
    if ($data=$this->database->table(self::TABLE)->select('*')->where(array('name'=>$name,'id_user'=>$user))->limit(1)->fetch()){
      return new Miner($data,$this->database);
    }
    return null;
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
