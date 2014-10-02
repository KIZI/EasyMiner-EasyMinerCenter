<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\UsersRepository;

class UsersFacade {
  /** @var UsersRepository $usersRepository */
  private $usersRepository;

  public function __construct(UsersRepository $usersRepository){
    $this->usersRepository=$usersRepository;
  }

  /**
   * @param int $id
   * @return User|null
   */
  public function findUser($id){
    return $this->usersRepository->find($id);
  }

  /**
   * @param string $email
   * @return User|null
   */
  public function findUserByEmail($email){
    return $this->usersRepository->findBy(array('email'=>$email));
  }

  /**
   * @param User $user
   * @return bool
   */
  public function saveUser(User &$user){
    return $this->usersRepository->persist($user);
  }

} 