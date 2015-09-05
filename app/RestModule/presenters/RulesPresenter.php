<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializer;
use Drahak\Restful\Validation\IValidator;
use Nette\NotImplementedException;

/**FIXME swagger 2.0
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 */
class RulesPresenter extends BaseResourcePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

  #region akce pro manipulaci s rulesetem

    #region actionRead
    /**
     * Akce vracející konkrétní pravidlo
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Get(
     *   tags={"Rules"},
     *   path="/rules/{id}",
     *   summary="Get details of the rule",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     name="id",
     *     description="Rule ID",
     *     required=true,
     *     type="integer",
     *     in="path"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Rule details",
     *     @SWG\Schema(ref="#/definitions/RuleResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule was not found.")
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
    /**
     * Akce pro vytvoření nového uživatelského účtu na základě zaslaných hodnot
     * @SWG\Post(
     *   tags={"Rules"},
     *   path="/rules",
     *   summary="Create new rule set",
     *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
     *   produces={"application/json","application/xml"},
     *   consumes={"application/json","application/xml"},
     *   @SWG\Parameter(
     *     description="Rule",
     *     name="rule",
     *     required=true,
     *     @SWG\Schema(ref="#/definitions/RuleInput"),
     *     in="body"
     *   ),
     *   @SWG\Response(
     *     response=201,
     *     description="Rule created successfully, returns details of the Rule.",
     *     @SWG\Schema(ref="#/definitions/RuleResponse")
     *   ),
     *   @SWG\Response(response=404,description="Requested RuleSet was not found.")
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
            return true;
          }catch (\Exception $e){}
          return false;
        });
      }
      $this->input->field('description')->addRule(IValidator::MAX_LENGTH,'Maximal length of description is %d characters!',200);
    }
    #endregion actionCreate

    #region actionUpdate

    /**
     * @param int $id
     * @throws \Nette\Application\BadRequestException
     * @SWG\Put(
     *   tags={"Rules"},
     *   path="/rules/{id}",
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
     *     description="Rule",
     *     name="rule",
     *     required=true,
     *     @SWG\Schema(ref="#/definitions/RuleInput"),
     *     in="body"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Rule updated successfully. Returns details of the rule.",
     *     @SWG\Schema(ref="#/definitions/RuleResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule set was not found.")
     * )
     */
    public function actionUpdate($id){
      //prepare RuleSet from input values
      //FIXME not implemented!
      throw new NotImplementedException();
      /** @var RuleSet $ruleSet */
      $ruleSet=$this->findRuleWithCheckAccess($id);

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
            return true;
          }catch (\Exception $e){}
          return false;
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

/** TODO zkontrolovat
 * @SWG\Definition(
 *   definition="RuleResponse",
 *   title="Rule",
 *   required={"id","name"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(property="description",type="string",description="Description of the rule set"),
 *   @SWG\Property(property="rulesCount",type="boolean",description="Count of rules in the rule set")
 * )
 * @SWG\Definition(
 *   definition="RuleInput",
 *   title="Rule",
 *   required={"id","name"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule set"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the rule set"),
 *   @SWG\Property(property="description",type="string",description="Description of the rule set")
 * )
 *
 */