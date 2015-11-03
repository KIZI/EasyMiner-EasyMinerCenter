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
      $csv=$this->databasesFacade->prepareCsvFromDatabaseRows($dbTable,$testedRowsCount,self::ROWS_PER_TEST);

      //TODO otestování položek...
    }

    // TODO sestavení výsledků jednotlivých testování...
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



  /**
   * @param string $url
   * @param string $postData = ''
   * @param string $apiKey = ''
   * @return string - response data
   * @throws \Exception - curl error
   */
  private static function curlRequestResponse($url, $postData='', $apiKey=''){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    $headersArr=[
      'Content-Type: application/xml; charset=utf-8'
    ];
    if (!empty($apiKey)){
      $headersArr[]='Authorization: ApiKey '.$apiKey;
    }
    if ($postData!=''){
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      $headersArr[]='Content-length: '.strlen($postData);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);

    $responseData = curl_exec($ch);
    if(curl_errno($ch)){
      $exception=curl_error($ch);
      curl_close($ch);
      throw new \Exception($exception);
    }
    curl_close($ch);
    return $responseData;
  }
}