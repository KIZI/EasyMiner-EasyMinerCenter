<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\EasyMiner\Entities\KnowledgeBaseRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\KnowledgeBaseRuleRelationsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleRuleRelationsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleSetRuleRelationsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleSetsRepository;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;

/**
 * Class KnowledgeBaseFacade - facade for work with rules from Knowledge base saved in DB
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Přemysl Václav Duben
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class KnowledgeBaseFacade {
  /** @var  RuleSetsRepository $rulesRepository */
  private $ruleSetsRepository;
  /** @var  RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository */
  private $ruleSetRuleRelationsRepository;
    /** @var KnowledgeBaseRuleRelationsRepository $knowledgeBaseRuleRelationsRepository */
    private $knowledgeBaseRuleRelationsRepository;
    /** @var  MinersFacade $minersFacade */
    private $minersFacade;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
    /** @var  RuleRuleRelationsRepository $ruleComparingCache */
    private $ruleComparingCache;

    /**
     * @param RuleSetsRepository $ruleSetsRepository
     * @param RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository
     * @param KnowledgeBaseRuleRelationsRepository $knowledgeBaseRuleRelationsRepository
     * @param MinersFacade $minersFacade
     * @param RulesFacade $rulesFacade
     */
    public function __construct(RuleSetsRepository $ruleSetsRepository, RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository, KnowledgeBaseRuleRelationsRepository $knowledgeBaseRuleRelationsRepository, MinersFacade $minersFacade, RulesFacade $rulesFacade, RuleRuleRelationsRepository $ruleComparingCache){
        $this->ruleSetsRepository=$ruleSetsRepository;
        $this->ruleSetRuleRelationsRepository=$ruleSetRuleRelationsRepository;
        $this->knowledgeBaseRuleRelationsRepository=$knowledgeBaseRuleRelationsRepository;
        $this->minersFacade=$minersFacade;
        $this->rulesFacade=$rulesFacade;
        $this->ruleComparingCache=$ruleComparingCache;
    }

  /**
   * Method for finding rules in Knowledge base from datasource
   * @param RuleSet|int $ruleSet
   * @param int $minerId
   * @return Rule[]
   */
  public function findRulesByDatasource($ruleSet,$miner){
      $currentDatasource = $this->minersFacade->findMiner($miner)->getDataArr()['datasourceId'];
      $rulesInRuleset = $this->ruleSetRuleRelationsRepository->findAllRulesByRuleSet($ruleSet);
      $toReturn = [];
      foreach($rulesInRuleset as $rule){
          if($rule->task->miner->datasource->datasourceId === $currentDatasource){
              $toReturn[] = $rule;
          }
      }
      return $toReturn;
  }

    /**
     * Method for adding/updating relation between Rule and Rule from Knowledge base
     * @param KnowledgeBaseRuleRelation|int $rule
     * @param int $ruleSetId
     * @param int $KBrule
     * @param string $relation
     * @param int $rate
     * @return bool
     * @throws \Exception
     */
    public function addRuleToKBRuleRelation($ruleSetId, $rule, $KBrule, $relation, $rate){
        if($rule instanceof KnowledgeBaseRuleRelation){
            $ruleToKBRuleRelation = $rule;
        } else{
            $ruleToKBRuleRelation=new KnowledgeBaseRuleRelation();
            $ruleToKBRuleRelation->ruleSetId=$ruleSetId;
            $ruleToKBRuleRelation->ruleId=$rule;
        }
        $ruleToKBRuleRelation->knowledgeBaseRuleId=$KBrule;
        $ruleToKBRuleRelation->relation=$relation;
        $ruleToKBRuleRelation->rate=$rate;
        $ruleToKBRuleRelation->resultDate=new \DateTime();
        $result=$this->knowledgeBaseRuleRelationsRepository->persist($ruleToKBRuleRelation);
        return $result;
    }

    /**
     * Method for finding the best saved similarity
     * @param int $ruleId
     * @return KnowledgeBaseRuleRelation
     * @throws \Exception
     */
    public function findRuleSimilarity($ruleSetId, $ruleId){
        return $this->knowledgeBaseRuleRelationsRepository->findBy([
            'rule_set_id'=>$ruleSetId,
            'rule_id'=>$ruleId
        ]);
    }

    /**
     * Method for saving all new comparations
     * @param Array $results as INSERT array
     */
    public function saveComparingResults($results){
        if(!empty($results)){
            $this->ruleComparingCache->saveComparing($results);
        }
    }

    /**
     * Method for finding all previous comparations with rules from Rule set
     * @param $ruleId
     * @param $ruleSetId
     * @return array
     */
    public function getRulesComparingResults($ruleId, $ruleSetId){
        return $this->ruleComparingCache->getComparingHistory($ruleId, $ruleSetId);
    }

    /**
     * Method for saving decomposed name of rule in Rule set
     * @param Int $ruleId
     * @param String $decomposed
     */
    public function setDecomposedRuleSetRule($ruleId, $decomposed){
        $this->ruleSetRuleRelationsRepository->setDecomposed($ruleId, $decomposed);
    }

} 