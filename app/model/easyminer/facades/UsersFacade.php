<?php

namespace App\Model\EasyMiner\Facades;


use App\Libs\StringsHelper;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\UsersRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
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
    if ($user->isDetached() && $user->getDbPassword()==''){
      //při ukládání nového uživatele mu přiřadíme heslo...
      $user->setDbPassword(StringsHelper::randString(8));
    }
    return $this->usersRepository->persist($user);
  }

  /**
   * @param string $googleUserId
   * @param object $googleProfile
   * @throws AuthenticationException
   * @return User
   */
  public function authenticateUserFromGoogle($googleUserId,$googleProfile){
    $googleProfile->email=Strings::lower($googleProfile->email);
    $user=null;
    try{
      //zkusíme najít existujícího uživatele
      $user=$this->findUserByGoogleId($googleUserId);
      if ($user){
        //pokud máme uživatele dle facebookId, musíme zkontrolovat, jestli tu není kolize v mailech... (jestli nemáme uživatele registrovaného samostatně, který by měl zadaný stejný mail)
        if ($user->email!=$googleProfile->email){
          try{
            $userByMail=$this->findUserByEmail($googleProfile->email);
          }catch (\Exception $e){/*chyba nás nezajímá*/}
          if (isset($userByMail) && ($userByMail instanceof User)){
            //sloučení uživatelských účtů
            $user=$this->mergeUsers($userByMail,$user);
          }else{
            if ($user->email!=$googleProfile->email){
              $user->email=$googleProfile->email;
            }
          }
        }
      }else{
        //pokud ho zatím nemáme připárovaného, zkusíme uživatele najít podle mailu
        $user=$this->findUserByEmail($googleProfile->email);
      }
    }catch (\Exception $e){/*chybu ignorujeme (dala se čekat)*/}

    if (!$user){
      //registrace nového uživatele
      $user=new User();
      $user->email=$googleProfile->email;
      $user->googleId=$googleUserId;
      $user->name=$googleProfile->givenName.' '.$googleProfile->familyName;
      $user->active=true;
      $user->lastLogin=new DateTime();
      $this->usersRepository->persist($user);
    }else{
      //kontrola, jestli není uživatel zablokován
      if (!$user->active){
        throw new AuthenticationException('User account is blocked.',self::NOT_APPROVED);
      }
      //update info o stávajícím uživateli
      if ($user->name!=$googleProfile->givenName.' '.$googleProfile->familyName){
        $user->name=$googleProfile->givenName.' '.$googleProfile->familyName;;
      }
      $user->lastLogin=new DateTime();
      $this->saveUser($user);
    }
    //stáhnutí obrázku
    $this->saveUserImageFromUrl($user,$googleProfile->picture);
    return $this->getUserIdentity($user);
  }

  /**
   * @param string $facebookUserId
   * @param object $facebookMe
   * @throws AuthenticationException
   * @return User
   */
  public function authenticateUserFromFacebook($facebookUserId,$facebookMe){
    $facebookMe->email=Strings::lower($facebookMe->email);
    $user=null;
    try{
      //zkusíme najít existujícího uživatele
      $user=$this->findUserByFacebookId($facebookUserId);
      if ($user){
        //pokud máme uživatele dle facebookId, musíme zkontrolovat, jestli tu není kolize v mailech... (jestli nemáme uživatele registrovaného samostatně, který by měl zadaný stejný mail)
        if ($user->email!=$facebookMe->email){
          try{
            $userByMail=$this->findUserByEmail($facebookMe->email);
          }catch (\Exception $e){/*chyba nás nezajímá*/}
          if (isset($userByMail) && ($userByMail instanceof User)){
            //sloučení uživatelských účtů
            $user=$this->mergeUsers($userByMail,$user);
          }else{
            if ($user->email!=$facebookMe->email){
              $user->email=$facebookMe->email;
            }
          }
        }
      }else{
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
      $this->saveUser($user);
    }else{
      //kontrola, jestli není uživatel zablokován
      if (!$user->active){
        throw new AuthenticationException('User account is blocked.',self::NOT_APPROVED);
      }
      //update info o stávajícím uživateli
      if ($user->name!=$facebookMe->first_name.' '.$facebookMe->last_name){
        $user->name=$facebookMe->first_name.' '.$facebookMe->last_name;
      }
      $user->lastLogin=new DateTime();
      $this->usersRepository->persist($user);
    }
    //stáhnutí obrázku
    $this->saveUserImageFromUrl($user,'https://graph.facebook.com/'.$facebookUserId.'/picture?width=400&height=400');
    return $this->getUserIdentity($user);
  }


  /**
   * @param User|int $user
   * @param string $url
   */
  private function saveUserImageFromUrl($user,$url){
    //TODO dodělat stáhnutí obrázku a uložení na serveru (pro další použití)
  }

  /**
   * @param User $user
   * @param User $duplicateUser
   * @return User
   */
  public function mergeUsers(User $user, User $duplicateUser){
    //TODO not implemented! mělo by dojít ke sloučení uživatelského účtu z facebooku či googlu pod hlavní uživatelský účet
    return $duplicateUser;
  }

  /**
   *
   * @param array $credentials
   * @throws AuthenticationException
   * @return IIdentity
   */
  function authenticate(array $credentials) {
    list($email, $password) = $credentials;
    $email=Strings::lower($email);
    try{
      $user=$this->findUserByEmail($email);
    }catch (\Exception $e){
      throw new AuthenticationException('User account was not found.', self::IDENTITY_NOT_FOUND,$e);
    }
    if (!$user->active){
      throw new AuthenticationException('User account is blocked.', self::NOT_APPROVED);
    }
    if (!Passwords::verify($password,$user->password)){
      throw new AuthenticationException('Invalid combination of email and password.', self::IDENTITY_NOT_FOUND);
    }
    return $this->getUserIdentity($user);
  }

  /**
   * Funkce pro zaregistrování lokálního uživatelského účtu
   * @param array $params
   * @return User
   */
  public function registerUser($params){
    $user=new User();
    $user->email=$params['email'];
    $user->password=Passwords::hash($params['password']);
    $user->name=$params['name'];
    $user->active=true;
    $user->setDbPassword(StringsHelper::randString(8));
    $this->saveUser($user);
    return $user;
  }

  /**
   * @param User|int $user
   * @return Identity
   * @throws AuthenticationException
   * @throws \Exception
   */
  public function getUserIdentity($user){
    if (!($user instanceof User)){
      try{
        $user=$this->usersRepository->find($user);
      }catch (\Exception $e){
        throw new AuthenticationException('User account was not found.', self::IDENTITY_NOT_FOUND,$e);
      }
    }
    if (!$user->active){
      throw new AuthenticationException('User account is blocked.', self::INVALID_CREDENTIAL);
    }

    $rolesArr=array('authenticated');
    /*TODO uživatelské role
    $userRoles=$user->roles;
    if (!empty($userRoles)){
      foreach ($userRoles as $userRole){
        $rolesArr[]=$userRole->roleId;
      }
    }
    */
    return new Identity($user->userId,$rolesArr,array('name'=>$user->name,'email'=>$user->email));
  }
}