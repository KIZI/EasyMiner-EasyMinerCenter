<?php
namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;

/**
 * Interface IScoringDriver - rozhraní driveru pro evaluační službu
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 */
interface IScorerDriver {

  /**
   * @param $serverUrl
   * @param DatabaseFactory $databaseFactory
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array|null $params=null
   */
  public function __construct($serverUrl, DatabaseFactory $databaseFactory, XmlSerializersFactory $xmlSerializersFactory, $params=null);

  /**
   * Funkce pro evaluaci pravidel nalezených v rámci konkrétní úlohy
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource);

  /**
   * Funkce pro evaluaci pravidel v rámci konkrétního rule setu
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateRuleSet(RuleSet $ruleSet, Datasource $testingDatasource);

}