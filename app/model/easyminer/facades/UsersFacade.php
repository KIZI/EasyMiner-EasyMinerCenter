<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Authorizators\OwnerRole;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Entities\UserForgottenPassword;
use EasyMinerCenter\Model\EasyMiner\Repositories\UserForgottenPasswordsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\UsersRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Class UsersFacade
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class UsersFacade implements IAuthenticator{
  /** @var UsersRepository $usersRepository */
  private $usersRepository;
  /** @var  UserForgottenPasswordsRepository $userForgottenPasswordsRepository */
  private $userForgottenPasswordsRepository;
  /** @var string $usersPhotosDirectory */
  private $usersPhotosDirectory;
  /** @var string $usersPhotosDirectory */
  private $usersPhotosUrl;

  /**
   * @param string $usersPhotosDirectory
   * @param string $usersPhotosUrl
   * @param UsersRepository $usersRepository
   * @param UserForgottenPasswordsRepository $userForgottenPasswordsRepository
   */
  public function __construct($usersPhotosDirectory, $usersPhotosUrl, UsersRepository $usersRepository, UserForgottenPasswordsRepository $userForgottenPasswordsRepository){
    $this->usersPhotosDirectory=rtrim($usersPhotosDirectory,'/');
    $this->usersPhotosUrl=rtrim($usersPhotosUrl,'/');
    $this->usersRepository=$usersRepository;
    $this->userForgottenPasswordsRepository=$userForgottenPasswordsRepository;
  }

  public function findUserByApiKey($key){
    return $this->usersRepository->findBy(array('key'=>$key));
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
      //during the save, create a new database password for the user, if it is not configured yet
      $user->setDbPassword(StringsHelper::randString(8));
    }
    try{
      $apiKey=$user->apiKey;
    }catch (\Exception $e){
      $apiKey='';
    }
    if ($apiKey==''){
      //during the save, create a new random API KEY, if it is not configured yet
      $user->apiKey=StringsHelper::randString(20).round(rand(3911,89301)).StringsHelper::randString(20);
    }
    return $this->usersRepository->persist($user);
  }

  /**
   * Method for authentication of an User by google ID (if not existing, creates a new User account)
   * @param string $googleUserId
   * @param object $googleProfile
   * @return Identity
   * @throws AuthenticationException
   */
  public function authenticateUserFromGoogle($googleUserId,$googleProfile){
    $googleProfile->email=Strings::lower($googleProfile->email);
    $user=null;
    try{
      //try to find existing User by googleId
      $user=$this->findUserByGoogleId($googleUserId);
    }catch (\Exception $e){/*ignore the error, it is OK*/}
    if ($user){
      //we have there an existing User found by googleId; we have to check, if there is not a collision in e-mails with another User account (if we have not there an User with the given e-mail, but created using manual registration using combination of e-mail and password)
      if ($user->email!=$googleProfile->email){
        try{
          $userByMail=$this->findUserByEmail($googleProfile->email);
        }catch (\Exception $e){/*ignore the error, it is OK*/}
        if (isset($userByMail) && ($userByMail instanceof User)){
          //merge the Users (user accounts)
          $user=$this->mergeUsers($userByMail,$user);
        }else{
          if ($user->email!=$googleProfile->email){
            $user->email=$googleProfile->email;
          }
        }
      }
    }else{
      //the User is not registered by googleId - try to find an User account by e-mail gained from google oauth
      try{
        $user=$this->findUserByEmail($googleProfile->email);
        $user->googleId=$googleUserId;
      }catch (\Exception $e){/*ignore the error, it is OK*/}
    }

    if (!$user){
      //register new User
      $user=new User();
      $user->email=$googleProfile->email;
      $user->googleId=$googleUserId;
      $user->name=$googleProfile->givenName.' '.$googleProfile->familyName;
      $user->active=true;
      $user->lastLogin=new DateTime();
      $this->saveUser($user);
    }else{
      //check, if the User is not blocked
      if (!$user->active){
        throw new AuthenticationException('User account is blocked.',self::NOT_APPROVED);
      }
      //update the User account details
      if ($user->name!=$googleProfile->givenName.' '.$googleProfile->familyName){
        $user->name=$googleProfile->givenName.' '.$googleProfile->familyName;;
      }
      $user->lastLogin=new DateTime();
      $this->saveUser($user);
    }
    //download image from google profile
    $this->saveUserImageFromUrl($user,$googleProfile->picture);
    return $this->getUserIdentity($user);
  }

  /**
   * Method for authentication of an User by facebook ID (if not existing, creates a new User account)
   * @param string $facebookUserId
   * @param object $facebookMe
   * @return Identity
   * @throws AuthenticationException
   */
  public function authenticateUserFromFacebook($facebookUserId,$facebookMe){
    $facebookMe->email=Strings::lower($facebookMe->email);
    $user=null;
    try{
      //try to find an existing User
      $user=$this->findUserByFacebookId($facebookUserId);
    }catch (\Exception $e){/*ignore the error, it is OK*/}
    if ($user){
      //we have there an existing User found by facebookId; we have to check, if there is not a collision in e-mails with another User account (if we have not there an User with the given e-mail, but created using manual registration using combination of e-mail and password)
      if ($user->email!=$facebookMe->email){
        try{
          $userByMail=$this->findUserByEmail($facebookMe->email);
        }catch (\Exception $e){/*ignore the error, it is OK*/}
        if (isset($userByMail) && ($userByMail instanceof User)){
          //merge Users (user accounts)
          $user=$this->mergeUsers($userByMail,$user);
        }else{
          if ($user->email!=$facebookMe->email){
            $user->email=$facebookMe->email;
          }
        }
      }
    }else{
      //the User is not registered by facebookId - try to find an User account by e-mail gained from google oauth
      try{
        $user=$this->findUserByEmail($facebookMe->email);
        $user->facebookId=$facebookUserId;
      }catch (\Exception $e){/*ignore the error, it is OK*/}
    }


    if (!$user){
      //register new User account
      $user=new User();
      $user->email=$facebookMe->email;
      $user->facebookId=$facebookUserId;
      $user->name=$facebookMe->first_name.' '.$facebookMe->last_name;
      $user->active=true;
      $user->lastLogin=new DateTime();
      $this->saveUser($user);
    }else{
      //check, if the User is not blocked
      if (!$user->active){
        throw new AuthenticationException('User account is blocked.',self::NOT_APPROVED);
      }
      //update the User account details
      if ($user->name!=$facebookMe->first_name.' '.$facebookMe->last_name){
        $user->name=$facebookMe->first_name.' '.$facebookMe->last_name;
      }
      $user->lastLogin=new DateTime();
      $this->usersRepository->persist($user);
    }
    //download User image from facebook profile
    $this->saveUserImageFromUrl($user,'https://graph.facebook.com/'.$facebookUserId.'/picture?width=200&height=200');
    return $this->getUserIdentity($user);
  }

  /**
   * Method returning URL of the profile image of the given User (relative path)
   * @param User|int $user
   * @return string|null
   */
  public function getUserImageUrl($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    if (file_exists($this->usersPhotosDirectory.'/'.$user.'.jpg')){
      return $this->usersPhotosUrl.'/'.$user.'.jpg';
    }
    return null;
  }

  /**
   * Method for saving of a profile image of the User to the local disk
   * @param User|int $user
   * @param string $url
   */
  private function saveUserImageFromUrl($user,$url){
    if ($user instanceof User){
      $user=$user->userId;
    }
    if ($url!='' && $photo=file_get_contents($url)){
      file_put_contents($this->usersPhotosDirectory.'/'.$user.'.jpg',$photo);
    }
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
   * Method for the user authentication using API KEY
   * @param string $apiKey
   * @param User $user=null
   * @throws AuthenticationException
   * @return IIdentity
   */
  function authenticateUserByApiKey($apiKey, &$user=null) {
    $apiKeyArr=User::decodeUserApiKey($apiKey);
    try{
      $user=$this->findUser($apiKeyArr['userId']);
    }catch (\Exception $e){
      throw new AuthenticationException('User account was not found.', self::IDENTITY_NOT_FOUND,$e);
    }
    if (!$user->active){
      throw new AuthenticationException('User account is blocked.', self::NOT_APPROVED);
    }
    if ($user->apiKey!=$apiKeyArr['apiKey']){
      throw new AuthenticationException('The API key is not valid.', self::IDENTITY_NOT_FOUND);
    }
    return $this->getUserIdentity($user);
  }

  /**
   * Method for registration of a new local User account
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
    $user->lastLogin=new DateTime();
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

    $rolesArr=['authenticated',new OwnerRole($user->userId)];

    $imageUrl=$this->getUserImageUrl($user);

    /*TODO user roles
    $userRoles=$user->roles;
    if (!empty($userRoles)){
      foreach ($userRoles as $userRole){
        $rolesArr[]=$userRole->roleId;
      }
    }
    */
    return new Identity($user->userId,$rolesArr,array('name'=>$user->name,'email'=>$user->email,'imageUrl'=>$imageUrl,'apiKey'=>$user->getEncodedApiKey()));
  }

  /**
   * Method for preparation of a security code for the change of User password
   * @param User|int $user
   * @throws \Exception
   * @return UserForgottenPassword
   */
  public function generateUserForgottenPassword($user){
    if (!($user instanceof User)){
      $user=$this->findUser($user);
    }
    try{
      /** @var UserForgottenPassword $userForgottenPassword */
      $userForgottenPassword=$this->userForgottenPasswordsRepository->findBy(['user_id'=>$user->userId]);
      if (strtotime('-2DAY')>$userForgottenPassword->generated->getTimestamp()){
        $userForgottenPassword=new UserForgottenPassword();
        $userForgottenPassword->user=$user;
      }
    }catch (\Exception $e){/*chybu očekáváme...*/
      $userForgottenPassword=new UserForgottenPassword();
      $userForgottenPassword->user=$user;
    }
    $userForgottenPassword->generated=new DateTime();
    if (!($code=$userForgottenPassword->getCode())){
      $userForgottenPassword->setCode(StringsHelper::randString(10));
    }
    $this->userForgottenPasswordsRepository->persist($userForgottenPassword);
    return $userForgottenPassword;
  }

  /**
   * @param int $id
   * @return UserForgottenPassword
   * @throws \Exception
   */
  public function findUserForgottenPassword($id){
    return $this->userForgottenPasswordsRepository->find($id);
  }
  
  /**
   * Method for deletion of expired forgotten passwords (security codes for password renewal)
   * @param User|int $user
   */
  public function cleanForgottenPasswords($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    //remove security codes for the password change for the given user
    $userForgottenPasswords=$this->userForgottenPasswordsRepository->findAllBy(['user_id'=>$user]);
    if (!empty($userForgottenPasswords)){
      foreach($userForgottenPasswords as $userForgottenPassword){
        $this->userForgottenPasswordsRepository->delete($userForgottenPassword);
      }
    }
    //remove expired security codes for the password change
    $userForgottenPasswords=$this->userForgottenPasswordsRepository->findAllBy([['[generated] < %s',date('Y-m-d H:i:s',strtotime('-2DAY'))]]);
    if (!empty($userForgottenPasswords)){
      foreach($userForgottenPasswords as $userForgottenPassword){
        $this->userForgottenPasswordsRepository->delete($userForgottenPassword);
      }
    }
  }
}