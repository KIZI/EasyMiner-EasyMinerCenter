<?php
namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;

/**
 * Interface IScoringDriver - interface of drivers for evaluation services
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IScorerDriver {

  /**
   * @param string $serverUrl
   * @param DatabaseFactory $databaseFactory
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array|null $params=null
   */
  public function __construct($serverUrl, DatabaseFactory $databaseFactory, XmlSerializersFactory $xmlSerializersFactory, $params=null);

  /**
   * Method for evaluation of a given Task with association rules, testing using the given Datasource
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource);

  /**
   * Method for evaluation of a given RuleSet with association rules, testing using the given Datasource
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateRuleSet(RuleSet $ruleSet, Datasource $testingDatasource);

}