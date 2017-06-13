<?php
namespace EasyMinerCenter\Model\Scoring\ModelTester;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\CsvSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScoringResult;
use Nette\Application\LinkGenerator;
use Nette\NotImplementedException;

/**
 * Class ModelTesterScorer - driver for work with ModelTester (based on Drools)
 * @package EasyMinerCenter\Model\Scoring\ModelTester
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ModelTesterScorer implements IScorerDriver{
  /** @var  string $serverUrl */
  private $serverUrl;
  /** @var DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var  LinkGenerator $linkGenerator */
  public $linkGenerator;

  public $params=[];

  const ROWS_PER_TEST=1000;

  /**
   * @param string $serverUrl - ModelTester server URL
   * @param DatabaseFactory $databaseFactory
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array|null $params = null
   */
  public function __construct($serverUrl, DatabaseFactory $databaseFactory, XmlSerializersFactory $xmlSerializersFactory, $params=null){
    $this->serverUrl=trim($serverUrl,'/').'/association-rules/test-files';
    $this->databaseFactory=$databaseFactory;
    $this->params=$params;
  }

  /**
   * Method for evaluation of a given Task with association rules, testing using the given Datasource
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource) {
    $rulesXmlFileName=$task->taskId.'.xml';
    /** @var string $rulesXmlFilePath - cesta souboru s pravidly v XML */
    $rulesXmlFilePath=@$this->params['tempDirectory'].'/'.$rulesXmlFileName;

    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($task->rules);
    $rulesXml=$associationRulesXmlSerializer->getXml()->asXML();
    file_put_contents($rulesXmlFilePath,$rulesXml);

    $database=$this->databaseFactory->getDatabaseInstance($testingDatasource->getDbConnection(),$task->miner->user);
    $dbDatasource=$database->getDbDatasource($testingDatasource->dbDatasourceId>0?$testingDatasource->dbDatasourceId:$testingDatasource->dbTable);

    $dbRowsCount=$dbDatasource->size;
    $testedRowsCount=0;
    /** @var ScoringResult[] $partialResults */
    $partialResults=[];
    //export individual rows from DB and test them
    while($testedRowsCount<$dbRowsCount){
      $csv=CsvSerializer::prepareCsvFromDatabase($database,$dbDatasource,$testedRowsCount,self::ROWS_PER_TEST,';','"');

      $csvFileName=$testingDatasource->datasourceId.'-'.$testedRowsCount.'-'.self::ROWS_PER_TEST.'.csv';
      /** @var string $csvFilePath - path to CSV file with one part of data */
      $csvFilePath=@$this->params['tempDirectory'].'/'.$csvFileName;

      file_put_contents($csvFilePath,$csv);
      $url=$this->serverUrl.'?rulesXml='.$this->getTempFileUrl($rulesXmlFileName).'&dataCsv='.$this->getTempFileUrl($csvFileName);

      //try{
        $response=self::curlRequestResponse($url);
        $xml=simplexml_load_string($response);
        $partialResult=new ScoringResult();
        $partialResult->truePositive=(string)$xml->truePositive;
        $partialResult->falsePositive=(string)$xml->falsePositive;
        $partialResult->rowsCount=(string)$xml->rowsCount;
        $partialResults[]=$partialResult;
        unset($xml);
      //}catch (\Exception $e){
      //  /*ignore error...*/
      //}
      unlink($csvFilePath);
      $testedRowsCount+=self::ROWS_PER_TEST;
    }
    //merge the complex result
    return ScoringResult::merge($partialResults);
  }

  /**
   * Private method returning URL of application for building of links to download test data
   * @return string
   */
  private function getTempFileUrl($filename) {
    //FIXME HACK!
    $requestUri=substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/www'));
    $requestUri='http://'.$_SERVER['HTTP_HOST'].$requestUri;
    return $requestUri.'/temp/'.$filename;
  }


  /**
   * Method for evaluation of a given RuleSet with association rules, testing using the given Datasource
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateRuleSet(RuleSet $ruleSet, Datasource $testingDatasource) {
    // TODO: Implement evaluateRuleSet() method.
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