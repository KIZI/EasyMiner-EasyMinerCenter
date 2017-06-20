<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Validation\IField;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Class RuleSetsPresenter - presenter for work with rule sets
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RuleSetsPresenter extends BaseResourcePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

  #region actions for manipulation with a ruleset
    #region actionRead
    /**
     * Action returning details about one rule set or list of rule sets
     * @param int|null $id=null
     * @throws \Nette\Application\BadRequestException
     * @SWG\Get(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}",
     *   summary="Get details of the rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Rule set details",
     *     @SWG\Schema(ref="#/definitions/RuleSetResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
     * )
     */
    public function actionRead($id=null){
      if (empty($id)){
        //pokud není zadáno ID, chceme vypsat seznam všech rule setů pro daného uživatele
        $this->forward('list');
        return;
      }
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      $this->resource=$ruleSet->getDataArr();
      $this->sendResource();
    }
    #endregion actionRead

    #region actionDelete
    /**
     * Action for deleting a rule set
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Delete(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}",
     *   summary="Remove rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Rule set deleted successfully.",
     *     @SWG\Schema(ref="#/definitions/StatusResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
     * )
     */
    public function actionDelete($id){
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      //delete
      if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
        $this->resource=['code'=>200,'status'=>'OK'];
      }else{
        throw new BadRequestException();
      }
      $this->sendResource();
    }
    #endregion actionDelete

    #region actionCreate
    /**
     * Action for creating of a new ruleset
     * @SWG\Post(
     *   tags={"RuleSets"},
     *   path="/rule-sets",
     *   summary="Create new rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json", "application/xml"},
     *   consumes={"application/json", "application/xml"},
     *   @SWG\Parameter(
     *     description="RuleSet",
     *     name="ruleset",
     *     required=true,
     *     @SWG\Schema(ref="#/definitions/RuleSetInput"),
     *     in="body"
     *   ),
     *   @SWG\Response(
     *     response=201,
     *     description="RuleSet created successfully, returns details of RuleSet.",
     *     @SWG\Schema(ref="#/definitions/RuleSetResponse")
     *   ),
     *   @SWG\Response(
     *     response=422,
     *     description="Sent values are not acceptable!",
     *     @SWG\Schema(ref="#/definitions/InputErrorResponse")
     *   )
     * )
     */
    public function actionCreate(){
      //prepare RuleSet from input values
      $ruleSet=new RuleSet();
      /** @var array $data */
      $data=$this->input->getData();
      $ruleSet->name=$data['name'];
      $ruleSet->description=(!empty($data['description'])?$data['description']:'');
      $ruleSet->rulesCount=0;
      if ($user=$this->getCurrentUser()){
        $ruleSet->user=$user;
      }
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      //send response
      $this->actionRead($ruleSet->ruleSetId);
    }

    /**
     * Method for validation of input params for actionCreate()
     */
    public function validateCreate() {
      /** @var IField $fieldName */
      $fieldName=$this->input->field('name');
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $fieldName
        ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',3)
        ->addRule(IValidator::MAX_LENGTH,'Maximal length of name is  %d characters!',100)
        ->addRule(IValidator::REQUIRED,'Name is required!');
      if ($user=$this->getCurrentUser()){
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $fieldName->addRule(IValidator::CALLBACK,'RuleSet with this name already exists!',function($value)use($user){
          try{
            $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($value,$user,null);
            return true;
          }catch (\Exception $e){
            return false;
          }
        });
      }
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $this->input->field('description')->addRule(IValidator::MAX_LENGTH,'Maximal length of description is %d characters!',200);
    }
    #endregion actionCreate

    #region actionUpdate
    /**
     * Action for updating of detaild of a ruleset
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Put(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}",
     *   summary="Update existing rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   consumes={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Parameter(
     *     description="RuleSet",
     *     name="ruleset",
     *     required=true,
     *     in="body",
     *     @SWG\Schema(ref="#/definitions/RuleSetInput")
     *   ),
     *   @SWG\Response(
     *     response="200",
     *     description="Rule set details.",
     *     @SWG\Schema(ref="#/definitions/RuleSetResponse")
     *   ),
     *   @SWG\Response(
     *     response=422,
     *     description="Sent values are not acceptable!",
     *     @SWG\Schema(ref="#/definitions/InputErrorResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
     * )
     */
    public function actionUpdate($id){
      //prepare RuleSet from input values
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      $inputArr=$this->input->getData();
      $ruleSet->name=$inputArr['name'];
      /** @noinspection PhpUndefinedFieldInspection */
      $ruleSet->description=(!empty($inputArr['description'])?$inputArr['description']:'');
      if ($user=$this->getCurrentUser()){
        $ruleSet->user=$user;
      }
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      //send response
      $this->actionRead($ruleSet->ruleSetId);
    }

    /**
     * Method for validation of input params for actionUpdate()
     * @param int $id
     */
    public function validateUpdate($id){
      $fieldName=$this->input->field('name');
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $fieldName
        ->addRule(IValidator::MIN_LENGTH,'Minimal length of name is  %d characters!',3)
        ->addRule(IValidator::MAX_LENGTH,'Maximal length of name is  %d characters!',100)
        ->addRule(IValidator::REQUIRED,'Name is required!');
      if ($user=$this->getCurrentUser()){
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $fieldName->addRule(IValidator::CALLBACK,'RuleSet with this name already exists!',function($value)use($user,$id){
          try{
            $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($value,$user,$id);
            return true;
          }catch (\Exception $e){
            return false;
          }
        });
      }
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $this->input->field('description')->addRule(IValidator::MAX_LENGTH,'Maximal length of description is %d characters!',200);
    }
    #endregion actionUpdate

    #region actionList
    /**
     * Action for reading a list of all rulesets for the current user
     * @SWG\Get(
     *   tags={"RuleSets"},
     *   path="/rule-sets",
     *   summary="List rule sets for the current user",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   consumes={"application/json","application/xml"},
     *   @SWG\Response(
     *     response=200,
     *     description="List of rule sets",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/RuleSetResponse")
     *     )
     *   )
     * )
     */
    public function actionList(){
      $result=[];
      $ruleSets=$this->ruleSetsFacade->findRuleSetsByUser($this->getCurrentUser());
      if (empty($ruleSets)) {
        //if there is no existing ruleset, create a new one
        $ruleSet=new RuleSet();
        $user=$this->usersFacade->findUser($this->user->id);
        $ruleSet->user=$user;
        $ruleSet->name=$user->name;
        $this->ruleSetsFacade->saveRuleSet($ruleSet);
        $result[]=$ruleSet->getDataArr();
      }else{
        foreach ($ruleSets as $ruleSet){
          $result[]=$ruleSet->getDataArr();
        }
      }
      $this->setXmlMapperElements('rulesets','ruleset');
      $this->resource=$result;
      $this->sendResource();
    }
    #endregion actionList
  #endregion actions for manipulation with a ruleset


  #region actions for manipulation with rules in rulesets

    #region actionReadRules
    /**
     * Action returning list of rules in a selected ruleset
     * @param int $id
     * @param string $rel = "" - type of relation between ruleset and rules
     * @SWG\Get(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}/rules",
     *   summary="List rules saved in the selected rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Parameter(
     *     name="rel",
     *     description="Relation between rule set and rules: ''|'all'|'positive'|'neutral'|'negative'",
     *     required=false,
     *     type="string",
     *     enum={"","all","positive","negative","neutral"},
     *     in="query"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="List of rules",
     *     @SWG\Schema(
     *       @SWG\Property(property="ruleset",ref="#/definitions/RuleSetResponse"),
     *       @SWG\Property(
     *         property="rules",
     *         type="array",
     *         @SWG\Items(ref="#/definitions/RuleWithRelationResponse")
     *       )
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
     * )
     */
    public function actionReadRules($id,$rel=""){
      if ($rel=="all"){$rel="";}else{$rel=Strings::lower($rel);}
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleSetWithCheckAccess($id);
      //prepare the output
      $result=[
        'ruleset'=>$ruleSet->getDataArr()
      ];
      if (!empty($rel)){
        $ruleSetRuleRelations=$ruleSet->findRuleRelationsByType($rel);
      }else{
        $ruleSetRuleRelations=$ruleSet->ruleSetRuleRelations;
      }

      $rulesArr=[];
      if (!empty($ruleSetRuleRelations)){
        foreach($ruleSetRuleRelations as $ruleSetRuleRelation){
          $rule=$ruleSetRuleRelation->rule;
          $ruleDataArr=$rule->getBasicDataArr();
          $ruleDataArr['relation']=$ruleSetRuleRelation->relation;
          $rulesArr[]=$ruleDataArr;
        }
      }
      $result['rule']=$rulesArr;
      $this->resource=$result;
      $this->sendResource();
    }
    #endregion actionReadRules

    #region actionCreateRules
    /**
     * Action for adding rules to ruleset
     * @param int $id - ruleset ID
     * @param int|string $rules - IDs of rules for adding, separated with commas or semicolons
     * @param string $relation = 'positive'
     * @throws \Nette\Application\BadRequestException
     * @SWG\Post(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}/rules",
     *   summary="Add rules into the selected rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Parameter(
     *     name="rules",
     *     description="IDs of rules (optinally multiple - separated with , or ;)",
     *     required=true,
     *     type="array",
     *     @SWG\items(
     *       type="integer"
     *     ),
     *     collectionFormat="csv",
     *     in="query"
     *   ),
     *   @SWG\Parameter(
     *     name="relation",
     *     description="positive|neutral|negative",
     *     required=false,
     *     type="string",
     *     enum={"positive","negative","neutral"},
     *     in="query"
     *   ),
     *   @SWG\Response(
     *     response=201,
     *     description="Rules have been successfully added to the rule set.",
     *     @SWG\Schema(ref="#/definitions/StatusResponse"),
     *     examples={"code":201,"status":"OK"}
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
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
      $this->resource=['code'=>200,'status'=>'OK'];
      $this->sendResource();
    }
    #endregion actionCreateRules

    #region actionDeleteRules
    /**
     * Action for removing rules from the selected ruleset
     * @param int $id
     * @param int|string $rules
     * @throws \Nette\Application\BadRequestException
     * @SWG\Delete(
     *   tags={"RuleSets"},
     *   path="/rule-sets/{id}/rules",
     *   summary="Remove rules from the selected rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="RuleSet ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Parameter(
     *     name="rules",
     *     description="IDs of rules (optinally multiple - separated with , or ;)",
     *     required=true,
     *     type="array",
     *     @SWG\items(
     *       type="integer"
     *     ),
     *     collectionFormat="csv",
     *     in="query"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Rules have been removed from the rule set.",
     *     @SWG\Schema(ref="#/definitions/StatusResponse"),examples={"code":200,"status":"OK"}
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
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

      $this->resource=['code'=>200,'status'=>'ok'];
      $this->sendResource();
    }
    #endregion actionDeleteRules

  #endregion actions for manipulation with rules in rulesets



  /**
   * Private method for finding a ruleset by the $ruleSetId and checking the user permissions to work with it
   * @param int $ruleSetId
   * @return RuleSet
   * @throws \Nette\Application\BadRequestException
   */
  private function findRuleSetWithCheckAccess($ruleSetId){
    try{
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleSetId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested rule set was not found.');
      return null;
    }
    //check of user permissions
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->getCurrentUser()->userId);
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
 * @SWG\Definition(
 *   definition="RuleSetResponse",
 *   title="RuleSet",
 *   required={"id"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(property="description",type="string",description="Description of the rule set"),
 *   @SWG\Property(property="rulesCount",type="integer",description="Count of rules in the rule set"),
 *   @SWG\Property(property="lastModified",type="string",description="DateTime of last modification of rule set")
 * )
 * @SWG\Definition(
 *   definition="RuleSetInput",
 *   title="RuleSet",
 *   required={"name"},
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(property="description",type="string",description="Description of the rule set")
 * )
 * @SWG\Definition(
 *   definition="RuleWithRelationResponse",
 *   title="Rule",
 *   required={"id","text","relation"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule"),
 *   @SWG\Property(property="text",type="string",description="Human-readable form of the rule"),
 *   @SWG\Property(property="a",type="string",description="A value from the four field table"),
 *   @SWG\Property(property="b",type="string",description="B value from the four field table"),
 *   @SWG\Property(property="c",type="string",description="C value from the four field table"),
 *   @SWG\Property(property="d",type="string",description="D value from the four field table"),
 *   @SWG\Property(property="selected",type="string",enum={"0","1"},description="1, if the rule is in Rule Clipboard"),
 *   @SWG\Property(property="relation",type="string",enum={"positive","neutral","negative"},description="Relation to the current rule set")
 * )
 */