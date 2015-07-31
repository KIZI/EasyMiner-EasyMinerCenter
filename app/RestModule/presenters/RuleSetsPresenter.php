<?php

namespace App\RestModule\Presenters;

use App\Exceptions\EntityNotFoundException;
use App\Model\EasyMiner\Entities\RuleSet;
use App\Model\EasyMiner\Entities\RuleSetRuleRelation;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\EasyMiner\Facades\RuleSetsFacade;
use App\Model\EasyMiner\Facades\UsersFacade;
use Nette\InvalidArgumentException;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package App\KnowledgeBaseModule\Presenters
 */
class RuleSetsPresenter extends BaseResourcePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  /**
   * Akce vracející detaily konkrétního rule setu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Api(
   *   path="/rule-sets/{id}",
   *   @SWG\Operation(
   *     method="GET",
   *     summary="Get details of the rule set",
   *     authorizations="apiKey",
   *     @SWG\Parameter(
   *       name="id",
   *       description="RuleSet ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     type="RuleSetResponse",
   *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
   *   )
   * )
   */
  public function actionRead($id){
    try{
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested rule set was not found.');
      return;
    }
    //TODO zkontrolovat přístup k danému rule setu dle uživatelského účtu
    $this->resource=$ruleSet->getDataArr();
    $this->sendResource();
  }

  /**
   * Akce pro smazání zvoleného rule setu
   * @param int $id
   * @throws \Nette\Application\BadRequestException
   * @SWG\Api(
   *   path="/users/{id}",
   *   @SWG\Operation(
   *     method="DELETE",
   *     summary="Remove rule set",
   *     authorizations="apiKey",
   *     @SWG\Parameter(
   *       name="id",
   *       description="RuleSet ID",
   *       required=true,
   *       type="integer",
   *       paramType="path",
   *       allowMultiple=false
   *     ),
   *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
   *   )
   * )
   */
  public function actionDelete($id){
    try{
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    }catch (EntityNotFoundException $e){
      $this->error('Requested rule set was not found.');
      return;
    }
    //TODO $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);

    //smazání
    if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
      $this->resource=['state'=>'ok'];
    }
    $this->sendResource();
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


  /***********************************************************************************************************************/




  /**
   * Akce pro vypsání existujících rulesetů
   */
  public function actionList(){
    $ruleSets=$this->ruleSetsFacade->findRuleSetsByUser($this->user->id);
    $result=[];
    if (empty($ruleSets)) {
      //pokud není nalezen ani jeden RuleSet, jeden založíme...
      $ruleSet=new RuleSet();
      $user=$this->usersFacade->findUser($this->user->id);
      $ruleSet->user=$user;
      $ruleSet->name=$user->name;
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
    }else{
      foreach ($ruleSets as $ruleSet){
        $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
      }
    }
    $this->sendJsonResponse($result);
  }

  #region akce pro manipulaci s rulesetem

  /**
   * Akce pro vytvoření nového rulesetu (se zadaným jménem)
   * @param string $name
   * @param string $description=""
   * @throws InvalidArgumentException
   */
  public function actionNew($name, $description=""){
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id);
    //vytvoření rulesetu
    $ruleSet=new RuleSet();
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $ruleSet->user=$this->usersFacade->findUser($this->user->id);
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Akce pro přejmenování existujícího rulesetu
   * @param int $id
   * @param string $name
   * @param string $description=""
   */
  public function actionRename($id,$name,$description=""){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id,$ruleSet);
    //změna a uložení
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }


  #endregion akce pro manipulaci s rulesetem

  #region akce pro manipulaci s pravidly v rulesetech

  /**
   * Akce vracející jeden konkrétní ruleset se základním přehledem pravidel
   * @param int $id
   * @param int $offset
   * @param int $limit
   * @param string|null $order = null
   */
  public function actionGetRules($id,$offset=0,$limit=25,$order=null){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //připravení výstupu
    $result=[
      'ruleset'=>$ruleSet->getDataArr(),
      'rules'=>[]
    ];
    if ($ruleSet->rulesCount>0 || true){
      $rules=$this->ruleSetsFacade->findRulesByRuleSet($ruleSet,$order,$offset,$limit);
      if (!empty($rules)){
        foreach($rules as $rule){
          $result['rules'][$rule->ruleId]=$rule->getBasicDataArr();
        }
      }
    }
    $this->sendJsonResponse($result);
  }

  /**
   * Akce pro přidání pravidel do rulesetu
   * @param int $id
   * @param string|int $rules - ID pravidel oddělená čárkami či středníky
   * @param string $relation = 'positive'
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionAddRules($id,$rules,$relation=RuleSetRuleRelation::RELATION_POSITIVE, $result="simple"){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->addRuleToRuleSet($rule,$ruleSet,$relation);
        }catch (\Exception $e){continue;}
      }
    }
    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr,$ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Akce pro odebrání pravidel z rulesetu
   * @param int $id
   * @param string|int $rules
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionRemoveRules($id, $rules, $result="simple"){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleSet);
        }catch (\Exception $e){continue;}
      }
    }
    $this->ruleSetsFacade->updateRuleSetRulesCount($ruleSet);

    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr, $ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Akce pro odebrání všech pravidel z rulesetu
   * @param int $id
   */
  public function actionRemoveAllRules($id){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //smazání
    $this->ruleSetsFacade->removeAllRulesFromRuleSet($ruleSet);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Funkce připravující pole s informacemi o vybraných pravidlech pro vrácení v rámci JSON odpovědi
   * @param int[] $ruleIdsArr
   * @param RuleSet|int $ruleSet
   * @return array
   */
  private function prepareRulesResult($ruleIdsArr, $ruleSet=null){
    $result=[];
    if (!empty($ruleIdsArr)) {
      foreach ($ruleIdsArr as $ruleId) {
        if (!$ruleId) {
          continue;
        }
        try {
          $rule = $this->rulesFacade->findRule($ruleId);
          $result[$rule->ruleId]=$rule->getBasicDataArr();
          if (!empty($ruleSet)){
            $relation=$rule->getRuleSetRelation($ruleSet);
            if (!empty($relation)){
              $result[$rule->ruleId]['ruleSetRelation']=$relation->relation;
            }else{
              $result[$rule->ruleId]['ruleSetRelation']='';
            }
          }
        }catch (\Exception $e){continue;}
      }
    }
    return $result;
  }

  #endregion akce pro manipulaci s pravidly v rulesetech

  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  #endregion injections
}


/**
 * @SWG\Model(
 *   id="RuleSetResponse",
 *   required="id,name,email,active",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(name="description",type="string",description="Description of the rule set"),
 *   @SWG\Property(name="rulesCount",type="boolean",description="Count of rules in the rule set")
 * )
 * @SWG\Model(
 *   id="UserInput",
 *   required="name,email,password",
 *   @SWG\Property(name="name",type="string",description="Name of the user"),
 *   @SWG\Property(name="email",type="string",description="E-mail for the User"),
 *   @SWG\Property(name="password",type="string",description="Password of the User (required for new account or for password change)"),
 * )
 */