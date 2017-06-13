<?php
namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Security\AuthenticationException;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use Drahak\Restful\Resource;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\Responses\TextResponse;
use Nette\NotImplementedException;
use Nette\Security\Passwords;

/**
 * Class UsersPresenter - presenter for management of users
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 */
class UsersPresenter extends BaseResourcePresenter {

  /**
   * Action returning API KEY for a concrete user account
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
   * Action for reading details about an User
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
   * Action for deleting an User
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
   * Action for creating of a new User using the posted values
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
   *     @SWG\Schema(ref="#/definitions/UserInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="User account created successfully, returns details of User.",
   *     @SWG\Schema(ref="#/definitions/UserResponseWithApiKey")
   *   ),
   *   @SWG\Response(response=404,description="Requested user was not found.")
   * )
   */
  public function actionCreate(){
    //prepare User from input values
    $user=new User();
    /** @noinspection PhpUndefinedFieldInspection */
    $user->name=$this->input->name;
    /** @noinspection PhpUndefinedFieldInspection */
    $user->email=$this->input->email;
    /** @noinspection PhpUndefinedFieldInspection */
    $user->password=Passwords::hash($this->input->password);
    $user->active=true;
    $this->usersFacade->saveUser($user);
    //send response

    try{
      /** @var User $user */
      $user=$this->usersFacade->findUser($user->userId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested user was not found. User account creation failed.');
      return;
    }

    $this->resource=[
      'id'=>$user->userId,
      'name'=>$user->name,
      'email'=>$user->email,
      'active'=>$user->active,
      'apiKey'=>$user->getEncodedApiKey()
    ];
    $this->sendResource();
  }

  /**
   * Method for validation of input params for actionCreate()
   */
  public function validateCreate() {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('name')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',5)
      ->addRule(IValidator::REQUIRED,'Name is required!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
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
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('password')
      ->addRule(IValidator::REQUIRED,'Password is required!')
      ->addRule(IValidator::MIN_LENGTH,'Minimal length of password is %s characters!',6);
  }
  #endregion

  #region actionUpdate
  /**
   * Action for updating of an User
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
    //update send properties of the User
    if (!empty($this->input->name)){
      $user->name=$this->input->name;
    }
    if (!empty($this->input->email)){
      $user->email=$this->input->email;
    }
    if (!empty($this->input->password)){
      $user->password=$this->input->password;
    }
    //save the results and send User details
    $this->actionRead($id);
  }

  /**
   * Method for validation of input params for actionUpdate()
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
   * Startup method for check of authentization using API KEY
   * @param bool $allowAnonymous=false
   * @throws AuthenticationException
   * @throws \Drahak\Restful\Application\BadRequestException
   * @throws \Exception
   */
  public function startup($allowAnonymous=false){
    parent::startup(@$this->request->parameters['action']=='create' || $allowAnonymous);
  }
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
 * @SWG\Definition(
 *   definition="UserResponseWithApiKey",
 *   title="User",
 *   required={"id","name","email","active"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the user"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the user"),
 *   @SWG\Property(property="email",type="string",description="E-mail for the user"),
 *   @SWG\Property(property="active",type="boolean",description="Was the user account activated?"),
 *   @SWG\Property(property="apiKey",type="string",description="User API key - for usage with other API requests")
 * )
 */