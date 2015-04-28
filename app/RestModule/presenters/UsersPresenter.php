<?php
namespace App\RestModule\Presenters;

use App\Exceptions\EntityNotFoundException;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Resource;
use Drahak\Restful\Validation\IValidator;
use Nette\Http\IResponse;
use Nette\NotImplementedException;

class UsersPresenter extends BaseResourcePresenter {

  /**
   * @var UsersFacade $usersFacade
   */
  private $usersFacade;

  /**
   * Akce vracející detaily konkrétního uživatelského účtu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   */
  public function actionRead($id){
    $this->resource=['state'=>'ok'];
    $this->sendResource();
    return;

    try{
      /** @var User $user */
      $user=$this->usersFacade->findUser($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested user was not found.');
      return;
    }
    //TODO zkontrolovat přístup k danému uživatelskému účtu
    $this->resource=['id'=>$user->userId,'name'=>$user->name,'email'=>$user->email,'active'=>$user->active];
    $this->sendResource();
  }

  /**
   * Akce pro smazání uživatelského účtu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   */
  public function actionDelete($id){
    try{
      /** @var User $user */
      $user=$this->usersFacade->findUser($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested user was not found.');
      return;
    }
    //TODO zkontrolovat přístup k danému uživatelskému účtu

    throw new NotImplementedException();
    //TODO
  }

  #region actionCreate
  /**
   * Akce pro vytvoření nového uživatelského účtu na základě zaslaných hodnot
   */
  public function actionCreate(){
    //prepare User from input values
    $user=new User();
    $user->name=$this->input->name;
    $user->email=$this->input->email;
    $user->active=true;
    $this->usersFacade->saveUser($user);
    //send response
    $this->actionRead($user->userId);
  }

  /**
   * Funkce pro kontrolu vstupů pro vytvoření nového uživatelského účtu
   */
  public function validateCreate() {
    $this->input->field('name')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',5)
      ->addRule(IValidator::REQUIRED,'Name is required!');
    $this->input->field('email')
      ->addRule(IValidator::EMAIL,'You have to input valid e-mail address!')
      ->addRule(IValidator::REQUIRED,'E-mail is required!')
      ->addRule(IValidator::CALLBACK,'User account with this e-mail already exists!',function($value){
        try{
          $this->usersFacade->findUserByEmail($value);
          return false;
        }catch (\Exception $e){}
        return true;
      });
    $this->input->field('password')
      ->addRule(IValidator::REQUIRED,'Password is required!')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of password is %s characters!',6);
  }
  #endregion

  #region actionUpdate

  /**
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   */
  public function actionUpdate($id){
    try{
      /** @var User $user */
      $user=$this->usersFacade->findUser($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested user was not found.');
      return;
    }
    //TODO zkontrolovat přístup k danému uživatelskému účtu
    //aktualizace zaslaných údajů
    if (!empty($this->input->name)){
      $user->name=$this->input->name;
    }
    if (!empty($this->input->email)){
      $user->email=$this->input->email;
    }
    if (!empty($this->input->password)){
      $user->password=$this->input->password;
    }
    //uložení a odeslání výsledku
    $this->actionRead($id);
  }

  /**
   * Funkce pro kontrolu vstupů pro aktualizaci uživatelského účtu
   * @param int $id
   */
  public function validateUpdate($id){
    $this->input->field('name')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',5);
    $this->input->field('email')
      ->addRule(IValidator::EMAIL,'You have to input valid e-mail address!')
      ->addRule(IValidator::CALLBACK,'User account with this e-mail already exists!',function($value)use($id){
        try{
          $user=$this->usersFacade->findUserByEmail($value);
          if ($user->userId==$id){return true;}
          return false;
        }catch (\Exception $e){}
        return true;
      });
    $this->input->field('password')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of password is %s characters!',6);
  }
  #endregion

  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  
}