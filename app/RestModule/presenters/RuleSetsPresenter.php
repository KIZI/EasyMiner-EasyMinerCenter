<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializer;
use Drahak\Restful\Validation\IValidator;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   description="Management of rule sets",
 *   basePath="BASE_PATH",
 *   resourcePath="/rule-sets",
 *   produces="['application/json','application/xml']",
 *   consumes="['application/json','application/xml']",
 * )
 */
class RuleSetsPresenter extends BaseResourcePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  #region akce pro manipulaci s rulesetem

    #region actionRead
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
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      $this->resource=$ruleSet->getDataArr();
      $this->sendResource();
    }
    #endregion actionRead

    #region actionDelete
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
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);

      //smazání
      if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
        $this->resource=['state'=>'ok'];
      }
      $this->sendResource();
    }
    #endregion actionDelete

    #region actionCreate
    /**
     * Akce pro vytvoření nového uživatelského účtu na základě zaslaných hodnot
     * @SWG\Api(
     *   path="/rule-sets",
     *   @SWG\Operation(
     *     method="POST",
     *     summary="Create new rule set",
     *     type="RuleSetResponse",
     *     @SWG\Parameter(
     *       description="RuleSet",
     *       required=true,
     *       type="RuleSetInput",
     *       paramType="body"
     *     ),
     *     @SWG\ResponseMessages(
     *       @SWG\ResponseMessage(code=201,message="RuleSet created successfully, returns details of RuleSet."),
     *       @SWG\ResponseMessage(code=404,message="Requested RuleSet was not found.")
     *     )
     *   )
     * )
     */
    public function actionCreate(){
      //prepare RuleSet from input values
      $ruleSet=new RuleSet();
      /** @noinspection PhpUndefinedFieldInspection */
      $ruleSet->name=$this->input->name;
      /** @noinspection PhpUndefinedFieldInspection */
      $ruleSet->description=$this->input->description;
      $ruleSet->rulesCount=0;
      if ($user=$this->getCurrentUser()){
        $ruleSet->user=$user;
      }
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      //send response
      $this->actionRead($ruleSet->ruleSetId);
    }

    /**
     * Funkce pro kontrolu vstupů pro vytvoření nového uživatelského účtu
     */
    public function validateCreate() {
      $fieldName=$this->input->field('name');
      $fieldName
        ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',3)
        ->addRule(IValidator::MAX_LENGTH,'Maximal length of name is  %d characters!',100)
        ->addRule(IValidator::REQUIRED,'Name is required!');
      if ($user=$this->getCurrentUser()){
        $fieldName->addRule(IValidator::CALLBACK,'RuleSet with this name already exists!',function($value)use($user){
          try{
            $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($value,$user,null);
            return false;
          }catch (\Exception $e){}
          return true;
        });
      }
      $this->input->field('description')->addRule(IValidator::MAX_LENGTH,'Maximal length of description is %d characters!',200);
    }
    #endregion actionCreate

    #region actionUpdate

    /**
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Api(
     *   path="/rule-sets/{id}",
     *   @SWG\Operation(
     *     method="PUT",
     *     summary="Update existing rule set",
     *     authorizations="apiKey",
     *     @SWG\Parameter(
     *       name="id",
     *       description="RuleSet ID",
     *       required=true,
     *       type="integer",
     *       paramType="path",
     *       allowMultiple=false
     *     ),
     *     @SWG\Parameter(
     *       description="RuleSet",
     *       required=true,
     *       type="RuleSetInput",
     *       paramType="body"
     *     ),
     *     type="RuleSetResponse",
     *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
     *   )
     * )
     */
    public function actionUpdate($id){
      //prepare RuleSet from input values
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);

      /** @noinspection PhpUndefinedFieldInspection */
      $ruleSet->name=$this->input->name;
      /** @noinspection PhpUndefinedFieldInspection */
      $ruleSet->description=$this->input->description;
      $ruleSet->rulesCount=0;
      if ($user=$this->getCurrentUser()){
        $ruleSet->user=$user;
      }
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      //send response
      $this->actionRead($ruleSet->ruleSetId);
    }

    /**
     * Funkce pro kontrolu vstupů pro aktualizaci rule setu
     * @param int $id
     */
    public function validateUpdate($id){
      $fieldName=$this->input->field('name');
      $fieldName
        ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',3)
        ->addRule(IValidator::MAX_LENGTH,'Maximal length of name is  %d characters!',100)
        ->addRule(IValidator::REQUIRED,'Name is required!');
      if ($user=$this->getCurrentUser()){
        $fieldName->addRule(IValidator::CALLBACK,'RuleSet with this name already exists!',function($value)use($user,$id){
          try{
            /** @noinspection PhpUndefinedFieldInspection */
            $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($value,$user,$id);
            return false;
          }catch (\Exception $e){}
          return true;
        });
      }
      $this->input->field('description')->addRule(IValidator::MAX_LENGTH,'Maximal length of description is %d characters!',200);
    }
    #endregion actionUpdate

  #endregion akce pro manipulaci s rulesetem


  /**
   * Akce pro vypsání existujících rulesetů
   */
  public function actionList(){
    //FIXME
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




  #endregion akce pro manipulaci s rulesetem

  #region akce pro manipulaci s pravidly v rulesetech

    #region actionReadRules
    /**
     * Akce vracející jeden konkrétní ruleset se základním přehledem pravidel
     * @param int $id
     * @param string $rel = "" - typ vztahu pravidel k tomuto rulesetu
     * @SWG\Api(
     *   path="/rule-sets/{id}/rules",
     *   @SWG\Operation(
     *     method="GET",
     *     summary="List rules saved in the selected rule set",
     *     authorizations="apiKey",
     *     @SWG\Parameter(
     *       name="id",
     *       description="RuleSet ID",
     *       required=true,
     *       type="integer",
     *       paramType="path",
     *       allowMultiple=false
     *     ),
     *     @SWG\Parameter(
     *       name="rel",
     *       description="positive|neutral|negative",
     *       required=false,
     *       type="string",
     *       paramType="query",
     *       allowMultiple=false
     *     ),
     *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
     *   )
     * )
     */
    public function actionReadRules($id,$rel=""){
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      //připravení výstupu
      $result=[
        'ruleset'=>$ruleSet->getDataArr()
      ];
      if (!empty($rel)){
        $ruleSetRuleRelations=$ruleSet->findRuleRelationsByType($rel);
      }else{
        $ruleSetRuleRelations=$ruleSet->ruleSetRuleRelations;
      }
      if (!empty($ruleSetRuleRelations)){
        foreach($ruleSetRuleRelations as $ruleSetRuleRelation){
          $rule=$ruleSetRuleRelation->rule;
          $ruleDataArr=$rule->getBasicDataArr();
          $ruleDataArr['relation']=$ruleSetRuleRelation->relation;
          $result['rule'][]=$ruleDataArr;
        }
      }
      $this->resource=$result;
      $this->sendResource();
    }
    #endregion actionReadRules

    #region actionCreateRules
    /**
     * Akce pro přidání pravidel do rulesetu
     * @param int $id - ID rule setu
     * @param int|string $rules - ID pravidel oddělená čárkami či středníky
     * @param string $relation = 'positive'
     * @throws \Nette\Application\BadRequestException
     * @SWG\Api(
     *   path="/rule-sets/{id}/rules",
     *   @SWG\Operation(
     *     method="POST",
     *     summary="Add rules into the selected rule set",
     *     authorizations="apiKey",
     *     @SWG\Parameter(
     *       name="id",
     *       description="RuleSet ID",
     *       required=true,
     *       type="integer",
     *       paramType="path",
     *       allowMultiple=false
     *     ),
     *     @SWG\Parameter(
     *       name="rules",
     *       description="IDs of rules (optinally multiple - separated with , or ;)",
     *       required=true,
     *       type="string",
     *       paramType="query",
     *       allowMultiple=false
     *     ),
     *     @SWG\Parameter(
     *       name="relation",
     *       description="positive|neutral|negative",
     *       required=false,
     *       type="string",
     *       paramType="query",
     *       allowMultiple=false
     *     ),
     *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
     *   )
     * )
     */
    public function actionCreateRules($id,$rules,$relation=RuleSetRuleRelation::RELATION_POSITIVE){
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
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

      $this->resource=['state'=>'ok'];
      $this->sendResource();
    }
    #endregion actionCreateRules

    #region actionDeleteRules
    /**
     * Akce pro odebrání pravidel z rulesetu
     * @param int $id
     * @param int|string $rules
     * @throws \Nette\Application\BadRequestException
     * @SWG\Api(
     *   path="/rule-sets/{id}/rules",
     *   @SWG\Operation(
     *     method="DELETE",
     *     summary="Remove rules from the selected rule set",
     *     authorizations="apiKey",
     *     @SWG\Parameter(
     *       name="id",
     *       description="RuleSet ID",
     *       required=true,
     *       type="integer",
     *       paramType="path",
     *       allowMultiple=false
     *     ),
     *     @SWG\Parameter(
     *       name="rules",
     *       description="IDs of rules (optinally multiple - separated with , or ;)",
     *       required=true,
     *       type="string",
     *       paramType="query",
     *       allowMultiple=false
     *     ),
     *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
     *   )
     * )
     */
    public function actionDeleteRules($id, $rules){
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
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

      $this->resource=['state'=>'ok'];
      $this->sendResource();
    }
    #endregion actionDeleteRules

  #endregion akce pro manipulaci s pravidly v rulesetech



  /**
   * Funkce pro nalezení rule setu dle zadaného ID a kontrolu oprávnění aktuálního uživatele pracovat s daným rule setem
   * @param int $ruleSetId
   * @return RuleSet
   * @throws \Nette\Application\BadRequestException
   */
  private function findRuleSetWithCheckAccess($ruleSetId){//FIXME
    try{
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleSetId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested rule set was not found.');
      return null;
    }
    //TODO $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    return $ruleSet;
  }


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
  #endregion injections
}

/**
 * @SWG\Model(
 *   id="RuleSetResponse",
 *   required="id",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(name="description",type="string",description="Description of the rule set"),
 *   @SWG\Property(name="rulesCount",type="boolean",description="Count of rules in the rule set")
 * )
 * @SWG\Model(
 *   id="RuleSetInput",
 *   required="id,name",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(name="description",type="string",description="Description of the rule set")
 * )
 *
 */