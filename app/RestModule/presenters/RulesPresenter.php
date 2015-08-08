<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializer;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\Responses\TextResponse;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 */
class RulesPresenter extends BaseResourcePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  #region akce pro manipulaci s rulesetem

    #region actionRead
    /**
     * Akce vracející konkrétní pravidlo
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Api(
     *   path="/rules/{id}",
     *   @SWG\Operation(
     *     method="GET",
     *     summary="Get details of the rule",
     *     authorizations="apiKey",
     *     @SWG\Parameter(
     *       name="id",
     *       description="Rule ID",
     *       required=true,
     *       type="integer",
     *       paramType="path",
     *       allowMultiple=false
     *     ),
     *     type="RuleResponse",
     *     @SWG\ResponseMessage(code=404, message="Requested rule set was not found.")
     *   )
     * )
     */
    public function actionRead($id){
      /** @var Rule $rule */
      $rule=$this->findRuleWithCheckAccess($id);
      $xmlSerializer=new XmlSerializer();
      $xml=$xmlSerializer->ruleAsXml($rule);
      $this->sendXmlResponse($xml);
    }
    #endregion actionRead

    #region actionCreate
    /**FIXME
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

    /**FIXME
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


  /**
   * Funkce pro nalezení pravidla dle zadaného ID a kontrolu oprávnění aktuálního uživatele pracovat s daným pravidlem
   * @param int $ruleId
   * @return RuleSet
   * @throws \Nette\Application\BadRequestException
   */
  private function findRuleWithCheckAccess($ruleId){
    try{
      /** @var Rule $rule */
      $rule=$this->rulesFacade->findRule($ruleId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested rule was not found.');
      return null;
    }
    //TODO kontrola oprávnění
    return $rule;
  }


  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  #endregion injections
}

/**FIXME
 * @SWG\Model(
 *   id="RuleResponse",
 *   required="id",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(name="description",type="string",description="Description of the rule set"),
 *   @SWG\Property(name="rulesCount",type="boolean",description="Count of rules in the rule set")
 * )
 * @SWG\Model(
 *   id="RuleInput",
 *   required="id,name",
 *   @SWG\Property(name="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(name="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(name="description",type="string",description="Description of the rule set")
 * )
 *
 */