<?php
namespace EasyMinerCenter\Model\Mining\R;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\TaskState;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\EasyMiner\Serializers\TaskSettingsJson;
use EasyMinerCenter\Model\EasyMiner\Serializers\TaskSettingsSerializer;
use EasyMinerCenter\Model\Mining\IMiningDriver;
use Nette\InvalidArgumentException;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class RDriver - driver for mining using simple R service
 * @package EasyMinerCenter\Model\Mining\R
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RDriver implements IMiningDriver{
  /** @var  Task $task */
  private $task;
  /** @var  Miner $miner */
  private $miner;
  /** @var  array $minerConfig */
  private $minerConfig = null;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;
  /** @var array $params - default config params*/
  private $params;
  /** @var  User $user */
  private $user;
  /** @var  string $apiKey - API KEY for the current user */
  private $apiKey;

  #region constants for mining delay/max requests
  const MAX_MINING_REQUESTS=5;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion constants for mining delay/max requests

  /**
   * Method for check of required interest measures in the current task config
   * @param TaskSettingsJson $taskSettingsJson
   */
  private function checkRequiredIMs(TaskSettingsJson $taskSettingsJson) {
    #region TODO dodělat kontrolu požadovaných měr zajímavosti podle nastavovacího XML (issue KIZI/EasyMiner-EasyMinerCenter#104)
    $usedIMNames=$taskSettingsJson->getIMNames();
    if (!in_array('AUTO_CONF_SUPP',$usedIMNames)){//FIXME jen dočasná úprava, je nutno kontrolovat dle konfiguračního souboru
      if (!in_array('CONF',$usedIMNames)){
        $taskSettingsJson->simpleAddIM('CONF',TaskSettingsJson::THRESHOLD_TYPE_PERCENTS,TaskSettingsJson::COMPARE_GTE,0.0001);
      }
      if (!in_array('SUPP',$usedIMNames)){
        $taskSettingsJson->simpleAddIM('SUPP',TaskSettingsJson::THRESHOLD_TYPE_PERCENTS,TaskSettingsJson::COMPARE_GTE,0.0001);
      }
    }
    #endregion
    #region check for RULE_LENGTH
    if (!in_array('RULE_LENGTH',$usedIMNames)){
      $attributeNames=$taskSettingsJson->getAttributeNames();
      $taskSettingsJson->simpleAddIM('RULE_LENGTH',TaskSettingsJson::THRESHOLD_TYPE_ABS,TaskSettingsJson::COMPARE_LTE,count($attributeNames));
    }
    #endregion check for RULE_LENGTH
  }

  /**
   * Method for starting of mining of the current task
   * @return TaskState
   * @throws \Exception
   */
  public function startMining() {
    #region check the task config in JSON
    $taskSettingsJson=new TaskSettingsJson($this->task->taskSettingsJson);
    $this->checkRequiredIMs($taskSettingsJson);
    #endregion check the task config in JSON

    #region serialize the task config to PMML
    $pmmlSerializer=$this->xmlSerializersFactory->createGuhaPmmlSerializer($this->task);
    $pmmlSerializer->appendMetabaseInfo();
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $taskSettingsSerializer->settingsFromJson($taskSettingsJson->getJsonString());
    #endregion serialize the task config to PMML

    $pmml=$taskSettingsSerializer->getPmml();
    //import the task and run the mining...
    $numRequests=1;
    sendStartRequest:
    try{
      $response=self::curlRequestResponse($this->getRemoteMinerUrl().'/mine', $pmml->asXML(),$this->getApiKey(),$responseCode);
      $taskState=$this->parseResponse($response,$responseCode);
    }catch (\Exception $e){
      if ((++$numRequests < self::MAX_MINING_REQUESTS)){sleep(self::REQUEST_DELAY); goto sendStartRequest;}
    }
    if (!empty($taskState)){
      return $taskState;
    }else{
      throw new \Exception('Task import failed!');
    }
  }

  /**
   * Method for checking the state of the current task
   * @throws \Exception
   * @return TaskState
   */
  public function checkTaskState(){
    if ($this->task->taskState->state==Task::STATE_IN_PROGRESS){
      if($this->task->resultsUrl!=''){
        $numRequests=1;
        sendStartRequest:
        try{
          #region check the task state and import results, if there are available
          $url=$this->getRemoteMinerUrl().'/'.$this->task->resultsUrl.'?apiKey='.$this->getApiKey();
          $response=self::curlRequestResponse($url,'',$this->getApiKey(),$responseCode);

          return $this->parseResponse($response,$responseCode);
          #endregion check the task state and import results, if there are available
        }catch (\Exception $e){
          if ((++$numRequests < self::MAX_MINING_REQUESTS)){sleep(self::REQUEST_DELAY); goto sendStartRequest;}
        }
      }else{
        $taskState=$this->task->taskState;
        $taskState->state=Task::STATE_FAILED;
        return $taskState;
      }
    }
    return $this->task->getTaskState();
  }

  #region Methods for check of the miner state and removing a miner
  /**
   * Method for stopping of the current task
   * @return TaskState
   */
  public function stopMining() {
    $taskState=$this->task->getTaskState();
    if ($taskState->state==Task::STATE_IN_PROGRESS){
      $taskState->state=Task::STATE_INTERRUPTED;
      $taskState->resultsUrl=null;
    }
    if ($taskState->importState!=Task::IMPORT_STATE_PARTIAL){
      $taskState->importState=Task::IMPORT_STATE_DONE;
    }
    return $taskState;
  }

  /**
   * Method for deleting the remote miner instance (on the remote mining server)
   * @return mixed|false
   */
  public function deleteMiner(){
    return true;
  }

  /**
   * Method for checking the configuration of the miner (including config params etc.)
   * @throws \Exception
   */
  public function checkMinerState(User $user){
    return true;
  }

  /**
   * Method returning the URL of a concrete miner on remote mining server (from config)
   * @return string
   */
  private function getRemoteMinerUrl(){
    $minerUrl=trim(@$this->params['minerUrl'],'/');
    return $this->getRemoteServerUrl().($minerUrl!=''?'/'.$minerUrl:'');
  }

  /**
   * Method returning the URL of remote mining server (from config)
   * @return string
   */
  private function getRemoteServerUrl(){
    return rtrim(@$this->params['server']);
  }

  /**
   * Method returning the current miner config
   * @return array
   */
  private function getMinerConfig(){
    if (!$this->minerConfig){
      $this->minerConfig=$this->miner->getConfig();
    }
    return $this->minerConfig;
  }

  /**
   * Method for setting the current miner config
   * @param array $minerConfig
   * @param bool $save = true
   */
  private function setMinerConfig($minerConfig,$save=true){
    $this->miner->setConfig($minerConfig);
    $this->minerConfig=$minerConfig;
    if ($save){
      $this->minersFacade->saveMiner($this->miner);
    }
  }

  #endregion Methods for check of the miner state and removing a miner

  /**
   * Methof for parsing the PMML response gained from remote mining server
   * @param string $pmmlString
   * @return TaskState
   * @throws \Exception
   */
  public function parseRulesPMML($pmmlString){
    file_put_contents($this->getImportPmmlPath(),$pmmlString);
    $this->basicParseRulesPMML($pmmlString,$rulesCount);
    $taskState=$this->task->getTaskState();
    $taskState->setRulesCount($taskState->getRulesCount()+$rulesCount);
    $taskState->importState=Task::IMPORT_STATE_WAITING;
    return $taskState;
  }

  /**
   * Method for parsing the basic info about rules in PMML (only text representation, IMs and IDs)
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - count of imported rules
   * @throws \Exception
   */
  public function basicParseRulesPMML($pmml,&$rulesCount=null){
    if ($pmml instanceof \SimpleXMLElement) {
      $xml=$pmml;
    }else{
      $xml=simplexml_load_string($pmml);
    }
    $xml->registerXPathNamespace('guha','http://keg.vse.cz/ns/GUHA0.1rev1');
    $guhaAssociationModel=$xml->xpath('//guha:AssociationModel');
    $rulesCount=(string)$guhaAssociationModel[0]['numberOfRules'];


    $associationRules=$xml->xpath('//guha:AssociationModel/AssociationRules/AssociationRule');

    if (empty($associationRules)){return;}//if there are no rules, do not import cedents...

    $rulesArr=[];
    foreach ($associationRules as $associationRule){
      $rule=new Rule();
      $rule->task=$this->task;
      $rule->text=(string)$associationRule->Text;
      $rule->pmmlRuleId=(string)$associationRule['id'];
      $fourFtTable=$associationRule->FourFtTable;
      $rule->a=(string)$fourFtTable['a'];
      $rule->b=(string)$fourFtTable['b'];
      $rule->c=(string)$fourFtTable['c'];
      $rule->d=(string)$fourFtTable['d'];
      $rulesArr[]=$rule;
    }
    $this->rulesFacade->saveRulesHeads($rulesArr);
    $this->rulesFacade->calculateMissingInterestMeasures($this->task);
  }


  /**
   * Method for full parsing of rules from PMML
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - count of imported rules
   * @param bool $updateImportedRulesHeads=false
   * @return bool
   * @throws \Exception
   */
  public function fullParseRulesPMML($pmml,&$rulesCount=null,$updateImportedRulesHeads=false){
    Debugger::log('fullParseRulesPMML start '.time(),ILogger::DEBUG);
    if ($pmml instanceof \SimpleXMLElement) {
      $xml=$pmml;
    }else{
      $xml=simplexml_load_string($pmml);
    }
    $xml->registerXPathNamespace('guha','http://keg.vse.cz/ns/GUHA0.1rev1');
    $guhaAssociationModel=$xml->xpath('//guha:AssociationModel');
    $guhaAssociationModel=$guhaAssociationModel[0];

    $rulesCount=(string)$guhaAssociationModel['numberOfRules'];
    /** @var \SimpleXMLElement $associationRulesXml */
    $associationRulesXml=$guhaAssociationModel->AssociationRules;

    if (count($associationRulesXml->AssociationRule)==0){return true;}//if there are no rules, do not process cedents...

    #region process cedent with only one subcedent
    $dbaItems=$associationRulesXml->xpath('./DBA[count(./BARef)=1]');
    $alternativeCedentIdsArr=array();
    if (!empty($dbaItems)){
      foreach ($dbaItems as $dbaItem){
        $idStr=(string)$dbaItem['id'];
        $BARefStr=(string)$dbaItem->BARef;
        $alternativeCedentIdsArr[$idStr]=(isset($alternativeCedentIdsArr[$BARefStr])?$alternativeCedentIdsArr[$BARefStr]:$BARefStr);
      }
    }
    if (!empty($alternativeCedentIdsArr)){
      do{
        $repeat=false;
        foreach ($alternativeCedentIdsArr as $id=>$referencedId){
          if (isset($alternativeCedentIdsArr[$referencedId])){
            $alternativeCedentIdsArr[$id]=$alternativeCedentIdsArr[$referencedId];
            $repeat=true;
          }
        }
      }while($repeat);
    }
    #endregion process cedent with only one subcedent

    /** @var Cedent[] $cedentsArr */
    $cedentsArr=[];
    /** @var RuleAttribute[] $ruleAttributesArr */
    $ruleAttributesArr=[];
    /** @var string[] $unprocessedIdsArr */
    $unprocessedIdsArr=[];
    /** @var ValuesBin[]|Value[] $valuesBinsArr */
    $valuesBinsArr=[];
    /** @var Attribute[] $attributesArr - array indexed using names of attributes used in PMML */
    $attributesArr=$this->miner->metasource->getAttributesByNamesArr();

    #region process rule attributes
    $bbaItems=$associationRulesXml->xpath('//BBA');
    if (!empty($bbaItems)){
      foreach ($bbaItems as $bbaItem){
        $ruleAttribute=new RuleAttribute();
        $idStr=(string)$bbaItem['id'];
        //save the relation to Attribute
        $attributeName=(string)$bbaItem->FieldRef;
        $valueName=(string)$bbaItem->CatRef;

        $attribute=$attributesArr[$attributeName];
        $ruleAttribute->attribute=$attribute;

        $valueItem=$attribute->preprocessing->findValue($valueName);
        if ($valueItem instanceof Value){
          $ruleAttribute->value=$valueItem;
        }elseif($valueItem instanceof ValuesBin){
          $ruleAttribute->valuesBin=$valueItem;
        }elseif($attribute->preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
          //if it is preprocessing "each-one" and the given value vas not found in DB, save the value...
          $value=new Value();
          $value->format=$attribute->preprocessing->format;
          $value->value=$valueName;
          $this->metaAttributesFacade->saveValue($value);
          $ruleAttribute->value=$value;
        }
        $this->rulesFacade->saveRuleAttribute($ruleAttribute);
        $ruleAttributesArr[$idStr]=$ruleAttribute;
      }
    }
    //cleanup the array with alternative IDs
    if (!empty($alternativeCedentIdsArr)){
      foreach ($alternativeCedentIdsArr as $id=>$alternativeId){
        if (isset($ruleAttributesArr[$alternativeId])){
          unset($alternativeCedentIdsArr[$id]);
        }
      }
    }
    #endregion process rule attributes
    #region process cedents with mode subcedents/values
    $dbaItems=$associationRulesXml->xpath('./DBA[count(./BARef)>0]');
    if (!empty($dbaItems)){
      do {
        foreach ($dbaItems as $dbaItem) {
          $idStr = (string)$dbaItem['id'];
          if (isset($cedentsArr[$idStr])) {
            continue;
          }
          if (isset($alternativeCedentIdsArr[$idStr])){
            continue;
          }
          if (isset($ruleAttributesArr[$idStr])) {
            continue;
          }
          $process = true;
          foreach ($dbaItem->BARef as $BARef) {
            $BARefStr = (string)$BARef;
            if (isset($alternativeCedentIdsArr[$BARefStr])) {
              $BARefStr = $alternativeCedentIdsArr[$BARefStr];
            }
            if (!isset($cedentsArr[$BARefStr]) && !isset($ruleAttributesArr[$BARefStr])) {
              $unprocessedIdsArr[$BARefStr] = $BARefStr;
              $process = false;
            }
          }
          if ($process) {
            unset($unprocessedIdsArr[$idStr]);
            //create concrete cedent
            $cedent = new Cedent();
            if (isset($dbaItem['connective'])) {
              $cedent->connective = Strings::lower((string)$dbaItem['connective']);
            } else {
              $cedent->connective = 'conjunction';
            }
            $this->rulesFacade->saveCedent($cedent);
            $cedentsArr[$idStr] = $cedent;
            foreach ($dbaItem->BARef as $BARef){
              $BARefStr = (string)$BARef;
              if (isset($alternativeCedentIdsArr[$BARefStr])) {
                $BARefStr = $alternativeCedentIdsArr[$BARefStr];
              }
              if (isset($cedentsArr[$BARefStr])){
                $cedent->addToCedents($cedentsArr[$BARefStr]);
              }elseif(isset($ruleAttributesArr[$BARefStr])){
                $cedent->addToRuleAttributes($ruleAttributesArr[$BARefStr]);
              }
            }
            $this->rulesFacade->saveCedent($cedent);
          } else {
            $unprocessedIdsArr[$idStr]=$idStr;
          }
        }
      }while(!empty($unprocessedIdsArr));
    }
    #endregion process cedents with mode subcedents/values

    #region association rules
    /** @var Cedent[] $topCedentsArr - array with top-level cedents (if a RuleAttribute is on the top level, it has to be packed in a cedent) */
    $topCedentsArr=array();

    foreach ($associationRulesXml->AssociationRule as $associationRule){
      $rule=false;
      if ($updateImportedRulesHeads){
        try{
          $rule=$this->rulesFacade->findRuleByPmmlImportId($this->task->taskId,(string)$associationRule['id']);
        } catch(\Exception $e){/*ignore*/}
      }
      if (!$rule){
        $rule=new Rule();
      }
      $rule->task=$this->task;
      #region antecedent
      if (isset($associationRule['antecedent'])){
        //it is rule with antecedent
        $antecedentId=(string)$associationRule['antecedent'];
        if (isset($alternativeCedentIdsArr[$antecedentId])){
          $antecedentId=$alternativeCedentIdsArr[$antecedentId];
        }

        if (isset($cedentsArr[$antecedentId])) {
          $rule->antecedent=($cedentsArr[$antecedentId]);
        }else{
          if (isset($ruleAttributesArr[$antecedentId])){
            if (!isset($topCedentsArr[$antecedentId])){
              $cedent=new Cedent();
              $cedent->connective = 'conjunction';
              $this->rulesFacade->saveCedent($cedent);
              $topCedentsArr[$antecedentId] = $cedent;
              $cedent->addToRuleAttributes($ruleAttributesArr[$antecedentId]);
              $this->rulesFacade->saveCedent($cedent);
            }
            $rule->antecedent=$topCedentsArr[$antecedentId];
          }else{
            throw new \Exception('Import failed!');
          }
        }

      }else{
        //it is rule without antecedent
        $rule->antecedent=null;
      }
      #endregion antecedent

      #region consequent
      $consequentId=(string)$associationRule['consequent'];
      if (isset($alternativeCedentIdsArr[$consequentId])){
        $consequentId=$alternativeCedentIdsArr[$consequentId];
      }

      if (isset($cedentsArr[$consequentId])) {
        $rule->consequent=($cedentsArr[$consequentId]);
      }else{
        if (isset($ruleAttributesArr[$consequentId])){
          if (!isset($topCedentsArr[$consequentId])){
            $cedent=new Cedent();
            $cedent->connective = 'conjunction';
            $this->rulesFacade->saveCedent($cedent);
            $topCedentsArr[$consequentId] = $cedent;
            $cedent->addToRuleAttributes($ruleAttributesArr[$consequentId]);
            $this->rulesFacade->saveCedent($cedent);
          }
          $rule->consequent=$topCedentsArr[$consequentId];
        }else{
          throw new \Exception('Import failed!');
        }
      }
      #endregion consequent

      $rule->text=(string)$associationRule->Text;
      $fourFtTable=$associationRule->FourFtTable;
      $rule->a=(string)$fourFtTable['a'];
      $rule->b=(string)$fourFtTable['b'];
      $rule->c=(string)$fourFtTable['c'];
      $rule->d=(string)$fourFtTable['d'];

      $this->rulesFacade->saveRule($rule);
    }
    $this->rulesFacade->calculateMissingInterestMeasures($this->task);
    #endregion association rules
    Debugger::log('fullParseRulesPMML end '.time(),ILogger::DEBUG);
    return true;
  }

  /**
   * Method for parsing of the response from the remote mining server
   * @param string $response
   * @param int $responseCode
   * @return TaskState
   * @throws \Exception
   */
  private function parseResponse($response, $responseCode){
    //check the state code and the task state
    if ($responseCode==202 && $this->task->state==Task::STATE_NEW){
      //create new miner (run task)
      $body=simplexml_load_string($response);
      if (substr($body->code,0,3)==202){
        //if the response is in a suitable format, save to the task, where to find the partial response
        $this->task->state=Task::STATE_IN_PROGRESS;
        if (!empty($body->miner->{'partial-result-url'})){
          $resultUrl='partial-result/'.$body->miner->{'task-id'};
        }
        return new TaskState($this->task,Task::STATE_IN_PROGRESS,null,!empty($resultUrl)?$resultUrl:'');
      }
    }elseif($this->task->state==Task::STATE_IN_PROGRESS){
      #region process already running task
      switch ($responseCode){
        case 204:
          //it is task with no results yet
          return $this->task->getTaskState();
        case 206:
          //import partial results
          return $this->parseRulesPMML($response);
        case 303:
          //all partial results have been loaded, finish the task (we do not need import the full PMML); the import is probably still running as a background request
          return new TaskState($this->task,Task::STATE_SOLVED,$this->task->rulesCount);
        case 404:
          if ($this->task->state==Task::STATE_IN_PROGRESS && $this->task->rulesCount>0){
            return new TaskState($this->task,Task::STATE_INTERRUPTED,$this->task->rulesCount);
          }else{
            return new TaskState($this->task,Task::STATE_FAILED);
          }
      }
      #endregion process already running task
    }
    //response is not in an acceptable format
    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
  }


  #region constructor
  /**
   * @param Task|null $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params = array()
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
    $this->params=$params;
    $this->rulesFacade=$rulesFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
    $this->user=$user;
    $this->xmlSerializersFactory=$xmlSerializersFactory;
    $this->setApiKey($user->getEncodedApiKey());
  }

  /**
   * Method for setting the current (active) task
   * @param Task $task
   */
  public function setTask(Task $task) {
    $this->task=$task;
    $this->miner=$task->miner;
  }

  #endregion constructor

  /**
   * @param string $url
   * @param string $postData = ''
   * @param string $apiKey = ''
   * @param int|null &$responseCode - variable returning the response code
   * @return string - response data
   * @throws \Exception - curl error
   */
  private static function curlRequestResponse($url, $postData='', $apiKey='', &$responseCode=null){
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
    $responseCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);

    if(curl_errno($ch)){
      $exception=curl_error($ch);
      curl_close($ch);
      throw new \Exception($exception);
    }
    curl_close($ch);
    return $responseData;
  }

  /**
   * Method returning path to a file, which should be imported
   * @return string
   */
  private function getImportPmmlPath(){
    $taskImportData=$this->task->getImportData();
    $uniqid=uniqid();
    $filename=$this->params['importsDirectory'].'/import_'.$this->task->taskId.'_'.$uniqid.'.pmml';
    $taskImportData[$uniqid]=$filename;
    $this->task->setImportData($taskImportData);
    return $filename;
  }


  #region apiKey

  /**
   * Method for setting the current API KEY
   * @param string $apiKey
   */
  public function setApiKey($apiKey){
    $this->apiKey=$apiKey;
  }

  /**
   * Method returning the current API KEY
   * @return string
   * @throws InvalidArgumentException
   */
  private function getApiKey(){
    if (empty($this->apiKey)){
      throw new InvalidArgumentException("Missing API KEY!");
    }
    return $this->apiKey;
  }

  #endregion apiKey

  /**
   * Method for checking, if the remote mining server is available
   * @param string $serverUrl
   * @throws \Exception
   * @return bool
   */
  public static function checkMinerServerState($serverUrl) {
    $response=self::curlRequestResponse($serverUrl);
    return !empty($response);
    //TODO implementace kontroly dostupnosti serveru
  }

  /**
   * Method for full import of task results in PMML
   * @return TaskState
   */
  public function importResultsPMML() {
    $importData=$this->task->getImportData();
    $taskState=$this->task->getTaskState();
    if (!empty($importData)){
      $filename=array_shift($importData);
      if (file_exists($filename)){
        //FIXME optimalizace načítání výsledků - vyřešení duplicitních cedentů (aby nedocházelo k jejich duplicitnímu importu
        $importedRulesCount=0;
        $this->fullParseRulesPMML(simplexml_load_file($filename),$importedRulesCount,true);

        #region update TaskState
        $taskState->importData=$importData;
        $taskState->importState=Task::IMPORT_STATE_WAITING;
        if (empty($importData)){
          if ($taskState->state==Task::STATE_SOLVED || $taskState->state==Task::STATE_INTERRUPTED){
            $taskState->importState=Task::IMPORT_STATE_DONE;
          }
        }
        #endregion update TaskState
        //delete imported file
        FileSystem::delete($filename);
      }
    }

    return $taskState;
  }

}