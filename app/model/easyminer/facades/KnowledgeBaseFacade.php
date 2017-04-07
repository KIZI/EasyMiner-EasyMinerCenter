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
 * Class KnowledgeBaseFacade - třída pro práci s pravidly v DB
 * @package EasyMinerCenter\Model\EasyMiner\Facades
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
     * Funkce pro přidání/změnu relace Rule k Rule v Knowledge Base
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
     * Získání již uložené největší podobnosti
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
     * Uložení všech nových porovnání
     * @param Array $results as INSERT array
     */
    public function saveComparingResults($results){
        if(!empty($results)){
            $this->ruleComparingCache->saveComparing($results);
        }
    }

    /**
     * Získání všech dřívějších porovnání pravidla s rulesetem
     * @param $ruleId
     * @param $ruleSetId
     * @return array
     */
    public function getRulesComparingResults($ruleId, $ruleSetId){
        return $this->ruleComparingCache->getComparingHistory($ruleId, $ruleSetId);
    }

    /**
     * Nastavení dekomponovaného tvaru pravidla v KB
     * @param Int $ruleId
     * @param String $decomposed
     */
    public function setDecomposedRuleSetRule($ruleId, $decomposed){
        $this->ruleSetRuleRelationsRepository->setDecomposed($ruleId, $decomposed);
    }

} 