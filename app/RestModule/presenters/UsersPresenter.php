<?php
namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Resource;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\Responses\TextResponse;
use Nette\NotImplementedException;

/**FIXME swagger 2.0
 * Class UsersPresenter - RESTFUL presenter for management of users
 * @package EasyMinerCenter\RestModule\Presenters
 *
 */
class UsersPresenter extends BaseResourcePresenter {

  /**
   * Akce vracející ApiKey konkrétního uživatelského účtu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Get(
   *   tags={"Users"},
   *   path="/users/{id}/apiKey",
   *   summary="Get API KEY for the selected user account",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"text/plain"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="User ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(response=404, description="Requested user was not found.")
   * )
   */
  public function actionReadApiKey($id){
    try{
      /** @var User $user */
      $user=$this->usersFacade->findUser($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested user was not found.');
      return;
    }
    //TODO zkontrolovat přístup k danému uživatelskému účtu
    $this->sendResponse(new TextResponse($user->getEncodedApiKey()));
  }

  /**
   * Akce vracející detaily konkrétního uživatelského účtu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Get(
   *   tags={"Users"},
   *   path="/users/{id}",
   *   summary="Get details of the user account",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="User ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="User details.",
   *     @SWG\Schema(ref="#/definitions/UserResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested user was not found.")
   * )
   */
  public function actionRead($id){
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
   * @FIXME-SWG-Delete(
   *   tags={"Users"},
   *   path="/users/{id}",
   *   summary="Remove user account",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @-SWG\Parameter(
   *     name="id",
   *     description="User ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @-SWG\Response(response=200, description="User deleted successfully.")
   *   @-SWG\Response(response=404, description="Requested user was not found.")
   * )
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
   * @SWG\Post(
   *   tags={"Users"},
   *   path="/users",
   *   summary="Create new user account",
   *   consumes={"application/json","application/xml"},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="user",
   *     description="User",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/UserResponse"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="User account created successfully, returns details of User.",
   *     @SWG\Schema(ref="#/definitions/UserResponse")
   *   ),
   *   @SWG\Response(response=404,description="Requested user was not found.")
   * )
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
   * Akce pro update existujícího uživatele
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Put(
   *   tags={"Users"},
   *   path="/users/{id}",
   *   summary="Update existing user account",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   consumes={"application/json","application/xml"},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="User ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Parameter(
   *     name="user",
   *     description="User",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/UserInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     @SWG\Schema(ref="#/definitions/UserResponse"),
   *     description="User details"),
   *   @SWG\Response(response=404, description="Requested user was not found.")
   * )
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
}

/**
 * @SWG\Definition(
 *   definition="UserResponse",
 *   title="User",
 *   required={"id","name","email","active"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the user"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the user"),
 *   @SWG\Property(property="email",type="string",description="E-mail for the user"),
 *   @SWG\Property(property="active",type="boolean",description="Was the user account activated?")
 * )
 * @SWG\Definition(
 *   definition="UserInput",
 *   title="User",
 *   required={"name","email","password"},
 *   @SWG\Property(property="name",type="string",description="Name of the user"),
 *   @SWG\Property(property="email",type="string",description="E-mail for the User"),
 *   @SWG\Property(property="password",type="string",description="Password of the User (required for new account or for password change)"),
 * )
 */