<?php

namespace App\Model\EasyMiner\Repositories;

use App\Model\EasyMiner\Entities\User;
use Nette;

class UsersRepository extends Nette\Object{
  /** @var \Nette\Database\Context */
  protected $database;
  const TABLE='users';

  public function __construct(Nette\Database\Context $database){
    $this->database = $database;
  }

  /**
   * @param int $id
   * @return User|null
   */
  public function findUser($id){
    if ($data=$this->database->table(self::TABLE)->select('*')->where('id_user=?',$id)->limit(1)->fetch()){
      return new User($data,$this->database);
    }
    return null;
  }

  /**
   * @param string $email
   * @return User|null
   */
  public function findUserByEmail($email){
    if ($data=$this->database->table(self::TABLE)->select('*')->where('email=?',$email)->limit(1)->fetch()){
      return new User($data,$this->database);
    }
    return null;
  }

  /**
   * @param User $user
   * @return bool|int|Nette\Database\Table\IRow
   */
  public function saveUser(User $user){
    if ($user->idUser){
      //update
      return $this->database->table(self::TABLE)->where($user->idUser)->update($user->getDataArr());
    }else{
      //insert
      return $this->database->table(self::TABLE)->insert($user->getDataArr());
    }
  }



} 