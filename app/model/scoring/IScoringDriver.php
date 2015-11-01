<?php
namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;

/**
 * Interface IScoringDriver - rozhraní driveru pro evaluační službu
 * @package EasyMinerCenter\Model\Scoring
 */
interface IScoringDriver {

  /**
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource);

  /**
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluareRuleSet(RuleSet $ruleSet, Datasource $testingDatasource);

}