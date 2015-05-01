<?php
namespace App\RestModule\Presenters;

use App\Exceptions\EntityNotFoundException;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Resource;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\Responses\TextResponse;
use Nette\NotImplementedException;

/**
 * Class UsersPresenter - RESTFUL presenter for management of users
 * @package App\RestModule\Presenters
 *
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   description="Management of user accounts",
 *   basePath="BASE_PATH",
 *   resourcePath="/users",
 *   produces="['application/json','application/xml']",
 *   consumes="['application/json','application/xml']",
 * )
 *
 */
class UsersPresenter extends BaseResourcePresenter {

  /**
   * @var UsersFacade $usersFacade
   */
  private $usersFacade;

  /**
   * Akce vracející ApiKey konkrétního uživatelského účtu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Api(
   *   path="/users/{id}/apiKey",
   *   @SWG\Operation(
   *     method="GET",
   *     summary="Get API KEY for the selected user account",
   *     authorizations="apiKey",
   *     produces="['text/plain']",
   *     @SWG\Parameter(
   *       name="id",
   *       description="User ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     @SWG\ResponseMessage(code=404, message="Requested user was not found.")
   *   )
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
   * @SWG\Api(
   *   path="/users/{id}",
   *   @SWG\Operation(
   *     method="GET",
   *     summary="Get details of the user account",
   *     authorizations="apiKey",
   *     @SWG\Parameter(
   *       name="id",
   *       description="User ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     type="UserResponse",
   *     @SWG\ResponseMessage(code=404, message="Requested user was not found.")
   *   )
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
   * @SWG\Api(
   *   path="/users/{id}",
   *   @SWG\Operation(
   *     method="DELETE",
   *     summary="Remove user account",
   *     authorizations="apiKey",
   *     @SWG\Parameter(
   *       name="id",
   *       description="User ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     @SWG\ResponseMessage(code=404, message="Requested user was not found.")
   *   )
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
   * @SWG\Api(
   *   path="/users",
   *   @SWG\Operation(
   *     method="POST",
   *     summary="Create new user account",
   *     type="UserResponse",
   *     @SWG\Parameter(
   *       description="User",
   *       required=true,
   *       type="UserInput",
   *       paramType="body"
   *     ),
   *     @SWG\ResponseMessages(
   *       @SWG\ResponseMessage(code=201,message="User account created successfully, returns details of User."),
   *       @SWG\ResponseMessage(code=404,message="Requested user was not found.")
   *     )
   *   )
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
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Api(
   *   path="/users/{id}",
   *   @SWG\Operation(
   *     method="PUT",
   *     summary="Update existing user account",
   *     authorizations="apiKey",
   *     @SWG\Parameter(
   *       name="id",
   *       description="User ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     @SWG\Parameter(
   *       description="User",
   *       required=true,
   *       type="UserInput",
   *       paramType="body"
   *     ),
   *     type="UserResponse",
   *     @SWG\ResponseMessage(code=404, message="Requested user was not found.")
   *   )
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

  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
}

/**
 * @SWG\Model(
 *   id="UserResponse",
 *   required="id,name,email,active",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the user"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the user"),
 *   @SWG\Property(name="email",type="string",description="E-mail for the user"),
 *   @SWG\Property(name="active",type="boolean",description="Was the user account activated?")
 * )
 * @SWG\Model(
 *   id="UserInput",
 *   required="name,email,password",
 *   @SWG\Property(name="name",type="string",description="Name of the user"),
 *   @SWG\Property(name="email",type="string",description="E-mail for the User"),
 *   @SWG\Property(name="password",type="string",description="Password of the User (required for new account or for password change)"),
 * )
 */