<?php
namespace EasyMinerCenter\Model\Scoring\ModelTester;


use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScoringResult;
use Nette\NotImplementedException;

class ModelTesterScorer implements IScorerDriver{
  /** @var  string $serverUrl */
  private $serverUrl;
  /** @var DatabasesFacade $databasesFacade */
  private $databasesFacade;

  const   ROWS_PER_TEST=100;

  /**
   * @param string $serverUrl - adresa koncového uzlu API, které je možné použít
   * @param DatabasesFacade $databasesFacade
   */
  public function __construct($serverUrl, DatabasesFacade $databasesFacade){
    $this->serverUrl=$serverUrl;
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource) {
    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($task->rules);
    $rulesXml=$associationRulesXmlSerializer->getXml();
    $dbTable=$testingDatasource->dbTable;

    $this->databasesFacade->openDatabase($testingDatasource->getDbConnection());
    $dbRowsCount=$this->databasesFacade->getRowsCount($dbTable);
    $testedRowsCount=0;
    //export jednotlivých řádků z DB a jejich otestování
    while($testedRowsCount<$dbRowsCount){
      $csv=$this->prepareCsvFromDatabaseRows($dbTable,$testedRowsCount,self::ROWS_PER_TEST);
      //TODO otestování položek...
    }

    // TODO sestavení výsledků jednotlivých testování...
  }

  /**
   * Funkce pro in-memory sestavení CSV souboru z vybraných řádků v DB
   * @param string $dbTable
   * @param int $limitStart
   * @param int $limitCount
   * @return string
   */
  private function prepareCsvFromDatabaseRows($dbTable,$limitStart,$limitCount){
    $rows=$this->databasesFacade->getRows($dbTable,$limitStart,$limitCount);
    $csv='';
    if (!empty($rows)){
      #region sestavení CSV
      $fd = fopen('php://temp/maxmemory:1048576', 'w');
      if($fd === FALSE) {
        die('Failed to open temporary file');
      }

      fputcsv($fd, array_keys($rows[0]));
      foreach($rows as $row) {
        fputcsv($fd, array_values($row));
      }

      rewind($fd);
      $csv = stream_get_contents($fd);
      fclose($fd);
      #endregion sestavení CSV
    }
    return $csv;
  }

  /**
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateRuleSet(RuleSet $ruleSet, Datasource $testingDatasource) {
    // TODO: Implement evaluareRuleSet() method.
    throw new NotImplementedException();
  }
}