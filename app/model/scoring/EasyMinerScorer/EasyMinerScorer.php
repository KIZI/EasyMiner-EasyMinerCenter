<?php
namespace EasyMinerCenter\Model\Scoring\EasyMinerScorer;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScoringResult;
use Nette\NotImplementedException;
use Nette\Utils\Json;
use Tracy\Debugger;

/**
 * Class EasyMinerScorer - driver for work with the scorer service EasyMinerScorer created by Jaroslav Kuchař
 * @package EasyMinerCenter\Model\Scoring\EasyMinerScorer
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class EasyMinerScorer implements IScorerDriver{
  /** @var  string $serverUrl */
  private $serverUrl;
  /** @var DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var array|null $params - array prepared for the work params of this driver*/
  public $params=[];

  const ROWS_PER_TEST=1000;

  /**
   * @param string $serverUrl - API endpoint URL
   * @param DatabaseFactory $databaseFactory
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array|null $params = null
   */
  public function __construct($serverUrl, DatabaseFactory $databaseFactory, XmlSerializersFactory $xmlSerializersFactory, $params=null){
    $this->serverUrl=trim($serverUrl,'/');
    $this->databaseFactory=$databaseFactory;
    $this->xmlSerializersFactory=$xmlSerializersFactory;
    $this->params=$params;
  }

  /**
   * Method for evaluation of a given Task with association rules, testing using the given Datasource
   * @param Task $task
   * @param Datasource $testingDatasource
   * @return ScoringResult
   * @throws \Exception
   */
  public function evaluateTask(Task $task, Datasource $testingDatasource) {
    #region prepare the Task PMML and create a instance of scorer using the scorer web service
    $pmml=$this->prepareTaskPmml($task);
    $url=$this->serverUrl.'/scorer';
    try{
      $response=self::curlRequestResponse($url,$pmml,'',['Content-Type'=>'application/xml; charset=utf-8']);

      $response=Json::decode($response,Json::FORCE_ARRAY);
      if (@$response['code']==201 && !empty($response['id'])){
        $scorerId=$response['id'];
      }else{
        throw new \Exception(@$response['description']);
      }
    }catch (\Exception $e){
      throw new \Exception('Scorer creation failed!',500,$e);
    }
    #region prepare the Task PMML and create a instance of scorer using the scorer web service

    #region continuous sending of rows from the testing data table (Datasource)
    $database=$this->databaseFactory->getDatabaseInstance($testingDatasource->getDbConnection(),$task->miner->user);
    $dbDatasource=$database->getDbDatasource($testingDatasource->dbDatasourceId>0?$testingDatasource->dbDatasourceId:$testingDatasource->dbTable);

    $dbRowsCount=$dbDatasource->size;
    $testedRowsCount=0;
    /** @var ScoringResult[] $partialResults */
    $partialResults=[];
    $url.='/'.$scorerId;
    //prepare JSON and send it
    $preloadedDbFields=null;
    //export individual data rows from the testing Datasource and test them...
    while($testedRowsCount<$dbRowsCount){
      $dbValuesRows=$database->getDbValuesRows($dbDatasource,$testedRowsCount,self::ROWS_PER_TEST,$preloadedDbFields);
      $dbValuesRowsData=$dbValuesRows->getRowsAsArray();
      if (!(count($dbValuesRowsData)>0)){
        throw new \Exception('Values serialization failed!');
      }
      $json=Json::encode($dbValuesRowsData);
      $responseStr=self::curlRequestResponse($url,$json,'',['Content-Type'=>'application/json; charset=utf-8']);

      $response=Json::decode($responseStr,Json::FORCE_ARRAY);
      if ($response["code"]!=200){
        Debugger::log('Invalid scorer response: '.$responseStr,Debugger::EXCEPTION);
        throw new \Exception('Invalid scorer response!');
      }

      //create new object with results
      $scoringResult=new EasyMinerScoringResult($response);
      $partialResult=$scoringResult->getScoringConfusionMatrix()->getScoringResult(true);
      $partialResults[]=$partialResult;
      //TODO there could be the processing of the complete confision matrix

      //add the count of tested rows and free the memory
      unset($scoringResult);
      $testedRowsCount+=self::ROWS_PER_TEST;
    }
    #region continuous sending of rows from the testing data table (Datasource)
    #region merging of the complete results
    return ScoringResult::merge($partialResults);
    #endregion merging of the complete results
  }


  /**
   * Method for preparation of PMML serialization of the given Task
   * @param Task $task
   * @return string
   */
  private function prepareTaskPmml(Task $task){
    $pmmlSerializer=$this->xmlSerializersFactory->createGuhaPmmlSerializer($task,null);
    $pmmlSerializer->appendTaskSettings();
    $pmmlSerializer->appendDataDictionary(false);
    $pmmlSerializer->appendTransformationDictionary(false);
    $pmmlSerializer->appendRules();
    return $pmmlSerializer->getPmml()->asXML();
  }


  /**
   * Method for evaluation of a given RuleSet with association rules, testing using the given Datasource
   * @param RuleSet $ruleSet
   * @param Datasource $testingDatasource
   * @return ScoringResult
   */
  public function evaluateRuleSet(RuleSet $ruleSet, Datasource $testingDatasource) {
    // TODO: Implement evaluareRuleSet() method.
    throw new NotImplementedException();
  }

  /**
   * Static method for CURL request
   * @param string $url
   * @param string $postData = ''
   * @param string $apiKey = ''
   * @param array $headersArr=[]
   * @return string - response data
   * @throws \Exception - curl error
   */
  private static function curlRequestResponse($url, $postData='', $apiKey='', $headersArr=[]){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    if (!empty($apiKey)){
      $headersArr['Authorization']='ApiKey '.$apiKey;
    }
    if ($postData!=''){
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      $headersArr['Content-length']=strlen($postData);
    }
    $headersSendArr=[];
    if (!empty($headersArr)){
      foreach ($headersArr as $header=>$value){
        $headersSendArr[]=$header.': '.$value;
      }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headersSendArr);

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