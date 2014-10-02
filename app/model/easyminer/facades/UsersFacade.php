<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\UsersRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

class UsersFacade implements IAuthenticator{
  /** @var UsersRepository $usersRepository */
  private $usersRepository;

  public function __construct(UsersRepository $usersRepository){
    $this->usersRepository=$usersRepository;
  }

  /**
   * @param int $id
   * @return User
   * @throws \Exception
   */
  public function findUser($id){
    return $this->usersRepository->find($id);
  }

  /**
   * @param string $email
   * @return User
   * @throws \Exception
   */
  public function findUserByEmail($email){
    return $this->usersRepository->findBy(array('email'=>$email));
  }

  /**
   * @param string $facebookId
   * @return User
   * @throws \Exception
   */
  public function findUserByFacebookId($facebookId){
    return $this->usersRepository->findBy(array('facebook_id'=>$facebookId));
  }

  /**
   * @param string $googleId
   * @return User
   * @throws \Exception
   */
  public function findUserByGoogleId($googleId){
    return $this->usersRepository->findBy(array('google_id'=>$googleId));
  }

  /**
   * @param User $user
   * @return bool
   */
  public function saveUser(User &$user){
    return $this->usersRepository->persist($user);
  }

  /**
   * @param $googleUserId
   * @param $googleProfile
   * @return User
   */
  public function authenticateUserFromGoogle($googleUserId,$googleProfile){
    //TODO
  }

  /**
   * @param $facebookUserId
   * @param $facebookMe
   * @return User
   */
  public function authenticateUserFromFacebook($facebookUserId,$facebookMe){
    $facebookMe->email=Strings::lower($facebookMe->email);
    $user=null;
    try{
      //zkusíme najít existujícího uživatele
      $user=$this->findUserByFacebookId($facebookUserId);
      if (!$user){
        //pokud ho zatím nemáme připárovaného, zkusíme uživatele najít podle mailu
        $user=$this->findUserByEmail($facebookMe->email);
      }
    }catch (\Exception $e){/*chybu ignorujeme (dala se čekat)*/}

    if (!$user){
      //registrace nového uživatele
      $user=new User();
      $user->email=$facebookMe->email;
      $user->facebookId=$facebookUserId;
      $user->name=$facebookMe->first_name.' '.$facebookMe->last_name;
      $user->active=true;
      $user->lastLogin=new DateTime();
      $this->usersRepository->persist($user);
    }else{
      //kontrola, jestli není uživatel zablokován
      if (!$user->active){
        throw new AuthenticationException('User account is blocked.',self::NOT_APPROVED);
      }
      //update info o stávajícím uživateli
      if ($user->email!=$facebookMe->email){
        $user->email=$facebookMe->email;
      }
    }






  }


  /**
   *
   * @return IIdentity
   * @throws AuthenticationException
   */
  function authenticate(array $credentials) {
    // TODO: Implement authenticate() method.
  }

  /**
   * @param User|int $user
   * @return IIdentity
   */
  public function getUserIdentity($user){
    //TODO
  }
}