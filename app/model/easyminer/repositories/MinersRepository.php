<?php

namespace App\Model\EasyMiner\Repositories;

use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\User;
use Nette;

class MinersRepository extends Nette\Object{
  /** @var \Nette\Database\Context */
  protected $database;
  const TABLE='miners';

  public function __construct(Nette\Database\Context $database){
    $this->database = $database;
  }

  /**
   * @param int $id
   * @return Miner|null
   */
  public function findMiner($id){
    if ($data=$this->database->table(self::TABLE)->select('*')->where(array('id_miner'=>$id))->limit(1)->fetch()){
      return new Miner($data,$this->database);
    }
    return null;
  }

  /**
   * @param int|User $user
   * @return Miner[]|null
   */
  public function findMinersByUser($user){
    if ($user instanceof User){
      $user=$user->idUser;
    }
    $result=array();
    if ($dataRows=$this->database->table(self::TABLE)->select('*')->where(array('id_user'=>$user))->fetchAll()){
      if (count($dataRows)){
        foreach ($dataRows as $data){
          $result[]=new Miner($data,$this->database);
        }
        return $result;
      }
    }
    return null;
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
   * @return bool|int|Nette\Database\Table\IRow
   */
  public function saveMiner(Miner $miner){
    if ($miner->idMiner){
      //update
      return $this->database->table(self::TABLE)->where(array('id_miner'=>$miner->idMiner))->update($miner->getDataArr());
    }else{
      //insert
      return $this->database->table(self::TABLE)->insert($miner->getDataArr());
    }
  }

  /**
   * @param Miner|int $miner
   * @return int
   */
  public function deleteMiner($miner){
    if ($miner instanceof Miner){
      $miner=$miner->idMiner;
    }
    return $this->database->table(self::TABLE)->where(array('id_miner'=>$miner))->delete();
  }




}