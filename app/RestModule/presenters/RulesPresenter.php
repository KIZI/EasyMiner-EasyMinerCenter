<?php

namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;

/**
 * Class RulesPresenter - presenter for work with individual rules
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
     *     @SWG\Schema(ref="#/definitions/RuleSimpleResponse")
     *   ),
     *   @SWG\Response(response=404, description="Requested rule was not found.")
     * )
     */
    public function actionRead($id){
      /** @var Rule $rule */
      $rule=$this->findRuleWithCheckAccess($id);
      $this->resource=$rule->getBasicDataArr();
      $this->setXmlMapperElements('rule');
      $this->sendResource();
    }
    #endregion actionRead

  /**
   * Private method returning Rule with given $ruleId, also checks user privileges
   * @param int $ruleId
   * @return Rule
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

/**
 * @SWG\Definition(
 *   definition="RuleSimpleResponse",
 *   title="Rule",
 *   required={"id","text"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the rule"),
 *   @SWG\Property(property="task",type="string",description="Task ID"),
 *   @SWG\Property(property="text",type="string",description="Human-readable form of the rule"),
 *   @SWG\Property(property="a",type="string",description="A value from the four field table"),
 *   @SWG\Property(property="b",type="string",description="B value from the four field table"),
 *   @SWG\Property(property="c",type="string",description="C value from the four field table"),
 *   @SWG\Property(property="d",type="string",description="D value from the four field table"),
 *   @SWG\Property(property="selected",type="string",enum={"0","1"},description="1, if the rule is in Rule Clipboard")
 * )
 */