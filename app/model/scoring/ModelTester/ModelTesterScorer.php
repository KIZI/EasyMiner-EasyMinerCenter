<?php
namespace EasyMinerCenter\Model\Scoring\ModelTester;

use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScoringResult;
use Nette\Application\LinkGenerator;
use Nette\NotImplementedException;

/**
 * Class ModelTesterScorer - driver pro práci s ModelTesterem (postaveným na Drools
 * @package EasyMinerCenter\Model\Scoring\ModelTester
 * @author Stanislav Vojíř
 */
class ModelTesterScorer implements IScorerDriver{
  /** @var  string $serverUrl */
  private $serverUrl;
  /** @var DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var  LinkGenerator $linkGenerator */
  public $linkGenerator;

  public $params=[];

  const ROWS_PER_TEST=1000;

  /**
   * @param string $serverUrl - adresa koncového uzlu API, které je možné použít
   * @param DatabasesFacade $databasesFacade
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array|null $params = null
   */
  public function __construct($serverUrl, DatabasesFacade $databasesFacade, XmlSerializersFactory $xmlSerializersFactory, $params=null){
    $this->serverUrl=trim($serverUrl,'/').'/association-rules/test-files';
    $this->databasesFacade=$databasesFacade;
    $this->params=$params;
  }

  /**
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
    $dbTable=$testingDatasource->dbTable;

    $this->databasesFacade->openDatabase($testingDatasource->getDbConnection());
    $dbRowsCount=$this->databasesFacade->getRowsCount($dbTable);
    $testedRowsCount=0;
    /** @var ScoringResult[] $partialResults */
    $partialResults=[];
    //export jednotlivých řádků z DB a jejich otestování
    while($testedRowsCount<$dbRowsCount){
      $csv=$this->databasesFacade->prepareCsvFromDatabaseRows($dbTable,$testedRowsCount,self::ROWS_PER_TEST,';','"');

      $csvFileName=$testingDatasource->datasourceId.'-'.$testedRowsCount.'-'.self::ROWS_PER_TEST.'.csv';
      /** @var string $csvFilePath - cesta k CSV souboru s částí dat */
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
    //XXX unlink($rulesXmlFilePath);
    //sestavení celkového výsledku
    return ScoringResult::merge($partialResults);
  }

  /**
   * Pracovní funkce vracející URL aplikace pro sestavení odkazů na stahování dat...
   * @return string
   */
  private function getTempFileUrl($filename) {
    //FIXME HACK!
    $requestUri=substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/www'));
    $requestUri='http://'.$_SERVER['HTTP_HOST'].$requestUri;
    return $requestUri.'/temp/'.$filename;
  }


  /**
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