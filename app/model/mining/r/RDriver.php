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
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializerFactory;
use EasyMinerCenter\Model\EasyMiner\Serializers\TaskSettingsJson;
use EasyMinerCenter\Model\EasyMiner\Serializers\TaskSettingsSerializer;
use EasyMinerCenter\Model\Mining\IMiningDriver;
use Nette\InvalidArgumentException;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

/**
 * Class RDriver - ovladač pro dolování za využití nové verze R driveru s postupným vracením výsledků
 * @package EasyMinerCenter\Model\Mining\R
 * @author Stanislav Vojíř
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
  /** @var GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory */
  private $guhaPmmlSerializerFactory;
  /** @var array $params - parametry výchozí konfigurace */
  private $params;
  /** @var  User $user */
  private $user;
  /** @var  string $apiKey - API KEY pro konktrétního uživatele */
  private $apiKey;

  #region konstanty pro dolování (před vyhodnocením pokusu o dolování jako chyby)
  const MAX_MINING_REQUESTS=5;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion

  /**
   * Funkce pro kontrolu nezbytných měr zajímavosti v nastavení úlohy
   * @param TaskSettingsJson $taskSettingsJson
   */
  private function checkRequiredIMs(TaskSettingsJson $taskSettingsJson) {
    #region TODO dodělat kontrolu požadovaných měr zajímavosti podle nastavovacího XML (issue #104)
    $usedIMNames=$taskSettingsJson->getIMNames();
    if (!in_array('FUI',$usedIMNames)){
      $taskSettingsJson->simpleAddIM('FUI',TaskSettingsJson::THRESHOLD_TYPE_PERCENTS,TaskSettingsJson::COMPARE_GTE,0.0001);
    }
    if (!in_array('SUPP',$usedIMNames)){
      $taskSettingsJson->simpleAddIM('SUPP',TaskSettingsJson::THRESHOLD_TYPE_PERCENTS,TaskSettingsJson::COMPARE_GTE,0.0001);
    }
    #endregion
    #region kontrola zadání RULE_LENGTH
    if (!in_array('RULE_LENGTH',$usedIMNames)){
      $attributeNames=$taskSettingsJson->getAttributeNames();
      $taskSettingsJson->simpleAddIM('RULE_LENGTH',TaskSettingsJson::THRESHOLD_TYPE_ABS,TaskSettingsJson::COMPARE_LTE,count($attributeNames));
    }
    #endregion kontrola zadání RULE_LENGTH
  }

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @return TaskState
   * @throws \Exception
   */
  public function startMining() {
    #region kontrola zadání úlohy v JSONu
    $taskSettingsJson=new TaskSettingsJson($this->task->taskSettingsJson);
    $this->checkRequiredIMs($taskSettingsJson);
    #endregion

    #region serializace zadání v PMML
    $pmmlSerializer=$this->guhaPmmlSerializerFactory->create($this->task);
    $pmmlSerializer->appendMetabaseInfo();
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $taskSettingsSerializer->settingsFromJson($taskSettingsJson->getJsonString());
    #endregion

    $pmml=$taskSettingsSerializer->getPmml();
    //import úlohy a spuštění dolování...
    $numRequests=1;
    sendStartRequest:
    try{
      #region pracovní zjednodušený request
      $response=self::curlRequestResponse($this->getRemoteMinerUrl().'/mine', $pmml->asXML(),$this->getApiKey(),$responseCode);
      $taskState=$this->parseResponse($response,$responseCode);
    #endregion
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
   * Funkce pro kontrolu aktuálního průběhu úlohy (aktualizace seznamu nalezených pravidel...)
   * @throws \Exception
   * @return TaskState
   */
  public function checkTaskState(){
    if ($this->task->taskState->state==Task::STATE_IN_PROGRESS){
      if($this->task->resultsUrl!=''){
        $numRequests=1;
        sendStartRequest:
        try{
          #region zjištění stavu úlohy, případně import pravidel
          $url=$this->getRemoteMinerUrl().'/'.$this->task->resultsUrl.'?apiKey='.$this->getApiKey();
          $response=self::curlRequestResponse($url,'',$this->getApiKey(),$responseCode);

          return $this->parseResponse($response,$responseCode);
          #endregion
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

  #region funkce pro smazání mineru, kontrolu jeho stavu a jeho smazání
  /**
   * Funkce pro zastavení dolování
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
   * Funkce volaná před smazáním konkrétního mineru
   * @return mixed|false
   */
  public function deleteMiner(){
    return true;
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @throws \Exception
   */
  public function checkMinerState(User $user){
    return true;
  }

  /**
   * Funkce vracející adresu R connectu
   * @return string
   */
  private function getRemoteMinerUrl(){
    $minerUrl=trim(@$this->params['minerUrl'],'/');
    return $this->getRemoteServerUrl().($minerUrl!=''?'/'.$minerUrl:'');
  }

  /**
   * Funkce vracející adresu serveru, kde běží R connect
   * @return string
   */
  private function getRemoteServerUrl(){
    return rtrim(@$this->params['server']);
  }

  /**
   * Funkce vracející konfiguraci aktuálně otevřeného mineru
   * @return array
   */
  private function getMinerConfig(){
    if (!$this->minerConfig){
      $this->minerConfig=$this->miner->getConfig();
    }
    return $this->minerConfig;
  }

  /**
   * Funkce nastavující konfiguraci aktuálně otevřeného mineru
   * @param array $minerConfig
   * @param bool $save = true
   */
  private function setMinerConfig($minerConfig,$save=true){//TODO
    $this->miner->setConfig($minerConfig);
    $this->minerConfig=$minerConfig;
    if ($save){
      $this->minersFacade->saveMiner($this->miner);
    }
  }

  #endregion

  /**
   * Funkce pro načtení pravidel z PMML získaného v rámci odpovědi serveru
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
   * Funkce pro načtení základu pravidel z PMML (jen text pravidla, IDčka a míry zajímavosti)
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - informace o počtu importovaných pravidel
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

    if (empty($associationRules)){return;}//pokud nejsou vrácena žádná pravidla, nemá smysl zpracovávat cedenty...

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
   * Funkce pro načtení PMML
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - informace o počtu importovaných pravidel
   * @param bool $updateImportedRulesHeads=false
   * @return bool
   * @throws \Exception
   */
  public function fullParseRulesPMML($pmml,&$rulesCount=null,$updateImportedRulesHeads=false){
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

    if (count($associationRulesXml->AssociationRule)==0){return true;}//pokud nejsou vrácena žádná pravidla, nemá smysl zpracovávat cedenty...

    #region zpracování cedentů jen s jedním subcedentem
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
      $repeat=true;
      while ($repeat){
        $repeat=false;
        foreach ($alternativeCedentIdsArr as $id=>$referencedId){
          if (isset($alternativeCedentIdsArr[$referencedId])){
            $alternativeCedentIdsArr[$id]=$alternativeCedentIdsArr[$referencedId];
            $repeat=true;
          }
        }
      }
    }
    #endregion
    /** @var Cedent[] $cedentsArr */
    $cedentsArr=[];
    /** @var RuleAttribute[] $ruleAttributesArr */
    $ruleAttributesArr=[];
    /** @var string[] $unprocessedIdsArr */
    $unprocessedIdsArr=[];
    /** @var ValuesBin[]|Value[] $valuesBinsArr */
    $valuesBinsArr=[];
    /** @var Attribute[] $attributesArr - pole indexované pomocí názvů atributů použitých v PMML */
    $attributesArr=$this->miner->metasource->getAttributesByNamesArr();
    #region zpracování rule attributes
    $bbaItems=$associationRulesXml->xpath('//BBA');
    if (!empty($bbaItems)){
      foreach ($bbaItems as $bbaItem){
        $ruleAttribute=new RuleAttribute();
        $idStr=(string)$bbaItem['id'];
        //uložení vazby na Attribute
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
          //pokud jde o preprocessing each-one a nebyla nalezena příslušná hodnota v DB, tak ji uložíme
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
    //pročištění pole s alternativními IDčky
    if (!empty($alternativeCedentIdsArr)){
      foreach ($alternativeCedentIdsArr as $id=>$alternativeId){
        if (isset($ruleAttributesArr[$alternativeId])){
          unset($alternativeCedentIdsArr[$id]);
        }
      }
    }
    #endregion

    #region zpracování cedentů s více subcedenty/hodnotami
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
            //vytvoření konkrétního cedentu...
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
    #endregion

    $topCedentsArr=array();

    foreach ($associationRulesXml->AssociationRule as $associationRule){
      $rule='';
      if ($updateImportedRulesHeads){
        try{
          $rule=$this->rulesFacade->findRuleByPmmlImportId($this->task->taskId,(string)$associationRule['id']);
        } catch(\Exception $e){/*ignore*/}
      }
      if (!$rule){
        $rule=new Rule();
      }
      $rule->task=$this->task;
      if (isset($associationRule['antecedent'])){
        //jde o pravidlo s antecedentem
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
        //jde o pravidlo bez antecedentu
        $rule->antecedent=null;
      }
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

      $rule->text=(string)$associationRule->Text;
      $fourFtTable=$associationRule->FourFtTable;
      $rule->a=(string)$fourFtTable['a'];
      $rule->b=(string)$fourFtTable['b'];
      $rule->c=(string)$fourFtTable['c'];
      $rule->d=(string)$fourFtTable['d'];
      $this->rulesFacade->saveRule($rule);
    }
    $this->rulesFacade->calculateMissingInterestMeasures($this->task);
    return true;
  }

  /**
   * Funkce parsující stav odpovědi od LM connectu
   * @param string $response
   * @param int $responseCode
   * @return TaskState
   * @throws \Exception
   */
  private function parseResponse($response, $responseCode){
    //kontrola stavového kódu odpovědi a stavu úlohy
    if ($responseCode==202 && $this->task->state==Task::STATE_NEW){
      //vytvoření nového mineru (spuštění úlohy)
      $body=simplexml_load_string($response);
      if (substr($body->code,0,3)==202){
        //pokud je odpověď ve vhodném formátu, uložíme k úloze info o tom, kde hledat dílčí odpověď
        $this->task->state=Task::STATE_IN_PROGRESS;
        if (!empty($body->miner->{'partial-result-url'})){
          $resultUrl='partial-result/'.$body->miner->{'task-id'};
        }
        return new TaskState(Task::STATE_IN_PROGRESS,null,!empty($resultUrl)?$resultUrl:'');
      }
    }elseif($this->task->state==Task::STATE_IN_PROGRESS){
      #region zpracování již probíhající úlohy
      switch ($responseCode){
        case 204:
          //jde o úlohu, která aktuálně nemá žádné další výsledky
          return $this->task->getTaskState();
        case 206:
          //import částečných výsledků
          return $this->parseRulesPMML($response);
        case 303:
          //byly načteny všechny částečné výsledky, ukončíme úlohu (import celého PMML již nepotřebujeme); na pozadí pravděpodobně ještě běží import
          return new TaskState(Task::STATE_SOLVED,$this->task->rulesCount);
        case 404:
          if ($this->task->state==Task::STATE_IN_PROGRESS && $this->task->rulesCount>0){
            return new TaskState(Task::STATE_INTERRUPTED,$this->task->rulesCount);
          }else{
            return new TaskState(Task::STATE_FAILED);
          }
      }
      #endregion zpracování již probíhající úlohy
    }
    //úloha není v žádném standartním tvaru...
    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
    //return new TaskState(Task::STATE_FAILED);
  }


  #region constructor
  /**
   * @param Task|null $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory
   * @param array $params = array()
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade, User $user, GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
    $this->params=$params;
    $this->rulesFacade=$rulesFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
    $this->user=$user;
    $this->guhaPmmlSerializerFactory=$guhaPmmlSerializerFactory;
    $this->setApiKey($user->getEncodedApiKey());
  }

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   * @return mixed
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
   * @param int|null &$responseCode - proměnná pro vrácení stavového kódu odpovědi
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
   * Funkce vracející cestu k souboru, který má být updatován
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
   * Funkce nastavující API klíč
   * @param string $apiKey
   */
  public function setApiKey($apiKey){
    $this->apiKey=$apiKey;
  }

  /**
   * Funkce vracející nastavený API klíč
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
   * Funkce pro kontrolu, jestli je dostupný dolovací server
   *
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
   * Funkce pro načtení plných výsledků úlohy z PMML
   *
   * @return TaskState
   */
  public function importResultsPMML() {
    #region zjištění jména souboru, který se má importovat (a kontrola, jestli tu nějaký takový soubor je)
    $importData=$this->task->getImportData();
    $taskState=$this->task->getTaskState();
    if (!empty($importData)){
      $filename=array_shift($importData);
      if (file_exists($filename)){
        //FIXME optimalizace načítání výsledků - vyřešení duplicitních cedentů (aby nedocházelo k jejich duplicitnímu importu
        $importedRulesCount=0;
        $this->fullParseRulesPMML(simplexml_load_file($filename),$importedRulesCount,true);

        #region aktualizace TaskState
        $taskState->importData=$importData;
        $taskState->importState=Task::IMPORT_STATE_WAITING;
        if (empty($importData)){
          if ($taskState->state==Task::STATE_SOLVED || $taskState->state==Task::STATE_INTERRUPTED){
            $taskState->importState=Task::IMPORT_STATE_DONE;
          }
        }
        #endregion aktualizace TaskState
        //smažeme importovaný soubor
        FileSystem::delete($filename);
      }
    }
    #endregion
    return $taskState;
  }

}