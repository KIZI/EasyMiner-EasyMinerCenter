<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
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
    /** @var  MinersFacade $minersFacade */
    private $minersFacade;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

    /**
     * @param RuleSetsRepository $ruleSetsRepository
     * @param RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository
     * @param MinersFacade $minersFacade
     * @param RulesFacade $rulesFacade
     */
    public function __construct(RuleSetsRepository $ruleSetsRepository, RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository, MinersFacade $minersFacade, RulesFacade $rulesFacade){
        $this->ruleSetsRepository=$ruleSetsRepository;
        $this->ruleSetRuleRelationsRepository=$ruleSetRuleRelationsRepository;
        $this->minersFacade=$minersFacade;
        $this->rulesFacade=$rulesFacade;
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

} 