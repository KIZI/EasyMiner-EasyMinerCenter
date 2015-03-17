<?php
namespace App\Model\Mining\LM;


use App\Model\Data\Entities\DbConnection;
use App\Model\EasyMiner\Entities\Cedent;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleAttribute;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Entities\TaskState;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\EasyMiner\Serializers\PmmlSerializer;
use App\Model\EasyMiner\Serializers\TaskSettingsSerializer;
use App\Model\Mining\IMiningDriver;
use Kdyby\Curl\CurlSender;
use Kdyby\Curl\Request as CurlRequest;
use Kdyby\Curl\Response as CurlResponse;
use Nette\Utils\Strings;
use Tracy\Debugger;

class LMDriver implements IMiningDriver{
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
  /** @var array $params - parametry výchozí konfigurace */
  private $params;
  /** LISp-Miner šablona pro export informací o stavu úlohy a počtu nalezených pravidel */
  const TASK_STATE_LM_TEMPLATE=null;//TODO
  /** LISp-Miner šablona pro export pravidel */
  const PMML_LM_TEMPLATE=null;//TODO
  #region konstanty pro dolování (před vyhodnocením pokusu o dolování jako chyby)
  const MAX_MINING_REQUESTS=10;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @return TaskState
   */
  public function startMining() {
    $pmmlSerializer=new PmmlSerializer($this->task);
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
    //import úlohy a spuštění dolování...
    $numRequests=1;
    sendRequest:
      $result=$this->queryPost($pmml,array('template'=>/*self::TASK_STATE_LM_TEMPLATE*/self::PMML_LM_TEMPLATE));
      $ok = (strpos($result, 'kbierror') === false && !preg_match('/status=\"failure\"/', $result));
      if ((++$numRequests < self::MAX_MINING_REQUESTS) && !$ok){sleep(self::REQUEST_DELAY); goto sendRequest;}

    $taskState=$this->parseTaskState($result);
    if ($taskState->rulesCount>0){
      //try{//FIXME
        $this->parseRulesPMML($result);
      //}catch (\Exception $e){}
    }
    return $taskState;
  }

  /**
   * Funkce pro kontrolu aktuálního průběhu úlohy (aktualizace seznamu nalezených pravidel...)
   * @throws \Exception
   * @return string
   */
  public function checkTaskState() {
    return $this->startMining();
  }

  /**
   * Funkce pro zastavení dolování
   * @return TaskState
   */
  public function stopMining() {
    $taskState=$this->task->getTaskState();
    if ($taskState->state==Task::STATE_SOLVED || $taskState->state!=Task::STATE_IN_PROGRESS){
      return $taskState;
    }
    try{
      $this->cancelRemoteMinerTask($this->getRemoteMinerTaskName());
      $this->task->state=Task::STATE_INTERRUPTED;
    }catch (\Exception $e){}

    return $this->task->getTaskState();
  }

  /**
   * @param string $result
   * @return TaskState
   * @throws \Exception
   */
  private function parseTaskState($result){
    if ($modelStartPos=strpos($result,'<guha:AssociationModel')){
      //jde o plné PMML
      $xml=simplexml_load_string($result);
      $xml->registerXPathNamespace('guha','http://keg.vse.cz/ns/GUHA0.1rev1');
      $guhaAssociationModel=$xml->xpath('//guha:AssociationModel');
      $taskState=new TaskState();
      if (count($guhaAssociationModel)!=1){throw new \Exception('Task state cannot be parsed!');/*došlo k chybě...*/}else{$guhaAssociationModel=$guhaAssociationModel[0];}
      $taskState->rulesCount=$guhaAssociationModel['numberOfRules'];
      $state=$guhaAssociationModel->xpath('TaskSetting//TaskState');
      $taskState->setState((string)$state[0]);
      return $taskState;
    }
    throw new \Exception('Task state cannot be parsed - UNKNOWN FORMAT!');
  }

  /**
   * Funkce pro načtení výsledků z DM nástroje a jejich uložení do DB

  public function importResults(){
    $pmmlSerializer=new PmmlSerializer($this->task);
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
    //import úlohy a spuštění dolování...
    $numRequests=1;
    sendRequest:
    $result=$this->queryPost($pmml,array('template'=>self::PMML_LM_TEMPLATE));
    $ok = (strpos($result, 'kbierror') === false && !preg_match('/status=\"failure\"/', $result));
    if ((++$numRequests < self::MAX_MINING_REQUESTS) && !$ok){sleep(self::REQUEST_DELAY); goto sendRequest;}

    return $this->parseRulesPMML($result);
  }*/

  /**
   * Funkce volaná před smazáním konkrétního mineru
   * @return mixed|false
   */
  public function deleteMiner() {
    try{
      $result=$this->unregisterRemoteMiner();
      return $result;
    }catch (\Exception $e){
      return false;
    }
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @throws \Exception
   */
  public function checkMinerState(){
    $minerConfig=$this->miner->getConfig();
    if ($metasource=$this->miner->metasource){
      if (empty($metasource->attributes)){//miner budeme kontrolovat jen v situaci, kdy máme alespoň jeden atribut... (do té doby to nemá smysl)
        return true;
      }
    }
    $lmId=$minerConfig['lm_miner_id'];
    if ($lmId){
      //pokud máme ID vzdáleného mineru, zkusíme otestovat, jestli existuje...
      if (!$this->testRemoteMinerExists()){
        $lmId=null;
      }
    }

    if (!$lmId){
      //zaregistrování nového mineru...
      try{
        $lmId=$this->registerRemoteMiner($this->miner->metasource->getDbConnection());
        $this->setRemoteMinerId($lmId);
      }catch (\Exception $e){
        throw new \Exception('LM miner registration failed!',$e->getCode(),$e);
      }
    }
    $existingAttributeNamesArr=array();
    try{
      $dataDescription=$this->getDataDescription();
      $attributesXml=simplexml_load_string($dataDescription);
      if (count($attributesXml->Attribute)>0){
        foreach($attributesXml->Attribute as $attributeName){
          $existingAttributeNamesArr[]=(string)$attributeName;
        }
      }
    }catch (\Exception $e){
      //při exportu se vyskytla chyba... (pravděpodobně nebyl zatím importován dataDictionary)
    }
    $attributes=$metasource->attributes;
    $attributeIdsArr=array();
    foreach ($attributes as $attribute){
      if (!in_array($attribute->name,$existingAttributeNamesArr)){
        $attributeIdsArr[]=$attribute->attributeId;
      }
    }
    if (count($attributeIdsArr)>0){
      //máme importovat nový atribut
      $pmml=$this->prepareImportPmml($attributeIdsArr);
      $this->importDataDictionary($pmml);
    }
    return true;
  }

  /**
   * Funkce připravující PMML pro import dataDictionary
   * @param int[] $attributesArr
   * @return \SimpleXMLElement
   */
  private function prepareImportPmml($attributesArr){
    $metasource=$this->miner->metasource;
    $pmml=simplexml_load_string('<?xml version="1.0"?>
<?oxygen SCHSchema="http://easyminer.eu/schemas/GUHARestr0_1.sch"?>
<PMML version="4.0" xmlns="http://www.dmg.org/PMML-4_0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:pmml="http://www.dmg.org/PMML-4_0" xsi:schemaLocation="http://www.dmg.org/PMML-4_0 http://easyminer.eu/schemas/PMML4.0+GUHA0.1.xsd">
  <Header>
    <Extension name="dataset" value="'.$metasource->attributesTable.'"/>
  </Header>
  <MiningBuildTask>
    <Extension name="DatabaseDictionary">
      <Table name="'.$metasource->attributesTable.'" reloadTableInfo="Yes">
        <PrimaryKey>
          <Column name="id" primaryKeyPosition="0"/>
        </PrimaryKey>
      </Table>
    </Extension>
  </MiningBuildTask>
  <DataDictionary></DataDictionary>
  <TransformationDictionary></TransformationDictionary>
</PMML>');
    $dataDictionary=$pmml->DataDictionary[0];
    $transformationDictionary=$pmml->TransformationDictionary[0];
    $metasourceAttributes=$metasource->attributes;
    foreach ($metasourceAttributes as $metasourceAttribute){
      if (in_array($metasourceAttribute->attributeId,$attributesArr)){
        $dataField=$dataDictionary->addChild('DataField');
        $dataField->addAttribute('name',$metasourceAttribute->name);
        $datasourceColumn=$metasourceAttribute->datasourceColumn;
        $derivedField=$transformationDictionary->addChild('DerivedField');
        $derivedField->addAttribute('name',$metasourceAttribute->name);
        $derivedField->addAttribute('dataType',$datasourceColumn->type);
        //TODO optype
        $mapValues=$derivedField->addChild('MapValues');
        $mapValues->addAttribute('outputColumn',$metasourceAttribute->name);
        $fieldColumnPair=$mapValues->addChild('FieldColumnPair');
        $fieldColumnPair->addAttribute('column',$metasourceAttribute->name);
        $fieldColumnPair->addAttribute('field',$metasourceAttribute->name);
        $autoDiscretize=$derivedField->addChild('AutoDiscretize');
        $autoDiscretize->addAttribute('type','Enumeration');
        $autoDiscretize->addAttribute('count','10000');
        $autoDiscretize->addAttribute('frequencyMin','1');
        $autoDiscretize->addAttribute('categoryOthers','No');
      }
    }
    return $pmml;
  }

  /**
   * Funkce vracející ID aktuálního vzdáleného mineru (lispmineru)
   * @return null|string
   */
  private function getRemoteMinerId(){
    $minerConfig=$this->getMinerConfig();
    if (isset($minerConfig['lm_miner_id'])){
      return $minerConfig['lm_miner_id'];
    }else{
      return null;
    }
  }

  /**
   * Funkce nastavující ID aktuálně otevřeného mineru
   * @param string|null $lmMinerId
   */
  private function setRemoteMinerId($lmMinerId){
    $minerConfig=$this->getMinerConfig();
    $minerConfig['lm_miner_id']=$lmMinerId;
    $this->setMinerConfig($minerConfig);
  }

  /**
   * Funkce vracející adresu LM connectu
   * @return string
   */
  private function getRemoteMinerUrl(){
    return @$this->params['server'];
  }

  /**
   * Funkce vracející typ požadovaného LM pooleru
   * @return string
   */
  private function getRemoteMinerPooler(){
    if (isset($this->params['pooler'])){
      return $this->params['pooler'];
    }else{
      return 'task';
    }
  }

  /**
   * Funkce vracející jméno úlohy na LM connectu taskUUID
   * @return string
   */
  private function getRemoteMinerTaskName(){
    return $this->task->taskUuid;
  }

  /**
   * @return string
   */
  private function getAttributesTableName(){
    return @$this->miner->metasource->attributesTable;
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
  private function setMinerConfig($minerConfig,$save=true){
    $this->miner->setConfig($minerConfig);
    $this->minerConfig=$minerConfig;
    if ($save){
      $this->minersFacade->saveMiner($this->miner);
    }
  }

  /**
   * Funkce pro načtení PMML
   * @param string $pmml
   * @return bool
   */
  public function parseRulesPMML($pmml){
    $xml=simplexml_load_string($pmml);
    $xml->registerXPathNamespace('guha','http://keg.vse.cz/ns/GUHA0.1rev1');
    $guhaAssociationModel=$xml->xpath('//guha:AssociationModel');
    $guhaAssociationModel=$guhaAssociationModel[0];
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
    $cedentsArr=array();
    /** @var RuleAttribute[] $ruleAttributesArr */
    $ruleAttributesArr=array();
    $unprocessedIdsArr=array();
    #region zpracování rule attributes
    $bbaItems=$associationRulesXml->xpath('//BBA');
    if (!empty($bbaItems)){
      foreach ($bbaItems as $bbaItem){
        $ruleAttribute=new RuleAttribute();
        $idStr=(string)$bbaItem['id'];
        $ruleAttribute->field=(string)$bbaItem->FieldRef;
        $ruleAttribute->value=(string)$bbaItem->CatRef;
        $ruleAttributesArr[$idStr]=$ruleAttribute;
        $this->rulesFacade->saveRuleAttribute($ruleAttribute);
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

    foreach ($associationRulesXml->AssociationRule as $associationRule){
      $rule=new Rule();
      $rule->task=$this->task;
      if (isset($associationRule['antecedent'])){
        //jde o pravidlo s antecedentem
        $antecedentId=(string)$associationRule['antecedent'];
        if (isset($alternativeCedentIdsArr[$antecedentId])){
          $antecedentId=$alternativeCedentIdsArr[$antecedentId];
        }
        $rule->antecedent=($cedentsArr[$antecedentId]);
      }else{
        //jde o pravidlo bez antecedentu
        $rule->antecedent=null;
      }

      $consequentId=(string)$associationRule['consequent'];
      if (isset($alternativeCedentIdsArr[$consequentId])){
        $consequentId=$alternativeCedentIdsArr[$consequentId];
      }
      $rule->consequent=($cedentsArr[$consequentId]);
      $rule->text=(string)$associationRule->Text;
      $fourFtTable=$associationRule->FourFtTable;
      $rule->a=(string)$fourFtTable['a'];
      $rule->b=(string)$fourFtTable['b'];
      $rule->c=(string)$fourFtTable['c'];
      $rule->d=(string)$fourFtTable['d'];
      $this->rulesFacade->saveRule($rule);
    }
    $this->rulesFacade->calculateMissingInterestMeasures();
    return true;
  }


  #region kód z KBI ==================================================================================================

  /**
   * @param DbConnection $dbConnection
   * @return string ID of registered miner.
   * @throws \Exception
   */
  private function registerRemoteMiner(DbConnection $dbConnection) {
    /** @var \SimpleXMLElement $requestXml */
    $requestXml = simplexml_load_string('<RegistrationRequest></RegistrationRequest>');

    /*if (isset($db_cfg['metabase'])) { //nastavení existující metabáze
      $metabaseXml = $requestXml->addChild('Metabase');
      $metabaseXml->addAttribute('type', 'Access');
      $metabaseXml->addChild('File', $db_cfg['metabase']);
    }*/

    if ($dbConnection->type!='mysql'){
      throw new \Exception('LMDriver supports only MySQL databases!');
    }

    $connectionXml = $requestXml->addChild('Connection');
    $connectionXml->addAttribute('type', $dbConnection->type);

    if ($dbConnection->dbPort!='') {
      $connectionXml->addChild('Server', $dbConnection->dbServer.':'.$dbConnection->dbPort);
    }else {
      $connectionXml->addChild('Server', $dbConnection->dbServer);
    }

    $connectionXml->addChild('Database', $dbConnection->dbName);

    if (!empty($dbConnection->dbUsername)) {
      $connectionXml->addChild('Username', $dbConnection->dbUsername);
    }
    if (!empty($dbConnection->dbUsername)) {
      $connectionXml->addChild('Username', $dbConnection->dbUsername);
    }

    if (!empty($dbConnection->dbPassword)) {
      $connectionXml->addChild('Password', $dbConnection->dbPassword);
    }

    $data = $requestXml->asXML();

    $url = $this->getRemoteMinerUrl().'/miners';

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->post($data);

    Debugger::fireLog($response);

    return $this->parseRegisterResponse($response);
  }

  /**
   * @param CurlResponse $response
   * @return string
   * @throws \Exception
   */
  private function parseRegisterResponse(CurlResponse $response) {
    $body = simplexml_load_string($response->getResponse());

    if ($response->getCode() != 200 || $body['status'] == 'failure') {
      throw new \Exception(isset($body->message) ? (string)$body->message : $response->getCode());
    } else if ($body['status'] == 'success') {
      return (string)$body['id'];
    }

    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
  }

  /**
   * Funkce pro smazání vzdáleného mineru
   * @return mixed
   */
  private function unregisterRemoteMiner() {
    $url = $this->getRemoteMinerUrl()."/miners/".$this->getRemoteMinerId();

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->delete();

    return $this->parseResponse($response, "Miner unregistered/removed.");
  }

  /**
   * Funkce pro import DataDictionary do LispMineru
   * @param string|\SimpleXMLElement $dataDictionary
   * @return string
   * @throws \Exception
   */
  private function importDataDictionary($dataDictionary) {
    if ($dataDictionary instanceof \SimpleXMLElement){
      $dataDictionary=$dataDictionary->asXML();
    }
    $remoteMinerId=$this->getRemoteMinerId();

    if(!$remoteMinerId){
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId.'/DataDictionary';

    $curlRequest=$this->prepareNewCurlRequest($url);

    $response = $curlRequest->put($dataDictionary);
    Debugger::fireLog($response, "Import executed");

    return $this->parseResponse($response);
  }

  /**
   * @param string $template
   * @return string
   * @throws \Exception
   */
  public function getDataDescription($template = 'LMDataSource.Matrix.ARD.Attributes.Template.XML') {
    $remoteMinerId = $this->getRemoteMinerId();

    if (!$remoteMinerId) {
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId.'/DataDictionary';

    $requestData = array(
      'matrix' => $this->getAttributesTableName(),
      'template' => $template
    );

    Debugger::fireLog(array($url, $requestData), "getting DataDictionary");

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->get($requestData);


    if ($response->isOk()) {
      return trim($response->getResponse());
    }

    return $this->parseResponse($response);
  }

  /**
   * @param \SimpleXMLElement|string $query
   * @param $options
   * @return string
   * @throws \Exception
   */
  public function queryPost($query, $options){
    if ($query instanceof \SimpleXMLElement){
      $query=$query->asXML();
    }

    $remoteMinerId = $this->getRemoteMinerId();
    if (!$remoteMinerId){
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl();

    $data = array();

    if (!empty($options['template'])) {
      $data['template'] = $options['template'];
      Debugger::fireLog("Using LM exporting template {$data['template']}");
    }

    if (isset($options['export'])) {
      $task = $options['export'];
      $url .= '/miners/'.$remoteMinerId.'/tasks/'.$task;

      Debugger::fireLog("Making just export of task '{$task}' (no generation).");
      Debugger::fireLog(array('URL' => $url, 'GET' => $data, 'POST' => $query));

      $curlRequest = $this->prepareNewCurlRequest($url);
      $response=$curlRequest->get($data);
    } else {
      $pooler = $this->getRemoteMinerPooler();

      if (isset($options['pooler'])) {
        $pooler = $options['pooler'];
        Debugger::log("Using '{$pooler}' as pooler");
      }

      switch ($pooler) {
        case 'grid':
          $url .= '/miners/'.$remoteMinerId.'/tasks/grid';
          break;
        case 'proc':
          $url .= '/miners/'.$remoteMinerId.'/tasks/proc';
          break;
        case 'task':
        default:
          $url .= '/miners/'.$remoteMinerId.'/tasks/task';
      }

      $curlRequest=$this->prepareNewCurlRequest($url);
      $curlRequest->getUrl()->appendQuery($options);
      try{
        $response = $curlRequest->post($query);
      }catch (\Exception $e){
        exit(var_dump($e));//FIXME
      }
    }
    if ($response->isOk()) {
      return $response->getResponse();
    }

    return $this->parseResponse($response);
  }

  /**
   * Funkce pro zastavení úlohy na LM connectu
   * @param string $taskName
   * @return string
   * @throws \Exception
   */
  public function cancelRemoteMinerTask($taskName) {
    /** @var \SimpleXMLElement $requestXml */
    $requestXml = simplexml_load_string("<CancelationRequest></CancelationRequest>");
    $remoteMinerId = $this->getRemoteMinerId();

    if (!$remoteMinerId) {
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl();

    switch ($this->getRemoteMinerPooler()) {
      case 'grid':
        $url .= '/miners/'.$remoteMinerId.'/tasks/grid/'.$taskName;
        break;
      case 'proc':
        $url .= '/miners/'.$remoteMinerId.'/tasks/proc/'.$taskName;
        break;
      case 'task':
      default:
        $url .= '/miners/'.$remoteMinerId.'/tasks/task/'.$taskName;
    }

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->put($requestXml->asXML());

    return $this->parseResponse($response);
  }

  /**
   * Funkce pro otestování existence LM connect mineru
   * @return bool
   */
  private function testRemoteMinerExists(){
    try {
      $remoteMinerId = $this->getRemoteMinerId();
      if (!$remoteMinerId) {
        throw new \Exception('LISpMiner ID was not provided.');
      }
      $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId;

      $curlRequest=$this->prepareNewCurlRequest($url);
      $response=$curlRequest->get();

      Debugger::fireLog($response, "Test executed");

      $this->parseResponse($response, '');

      return true;
    } catch (\Exception $ex) {
      return false;
    }
  }

  /**
   * Funkce parsující stav odpovědi od LM connectu
   * @param CurlResponse $response
   * @param string $message
   * @return string
   * @throws \Exception
   */
  private function parseResponse($response, $message = '') {
    $body = $response->getResponse();
    $body=simplexml_load_string($body);
    if (!$response->isOk() || $body['status'] == 'failure') {
      throw new \Exception(isset($body->message) ? (string)$body->message : $response->getCode());
    } else if ($body['status'] == 'success') {
      return isset($body->message) ? (string)$body->message : $message;
    }
    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response->getResponse())));
  }

  /**
   * @param $url
   * @return CurlRequest
   */
  private function prepareNewCurlRequest($url){
    $curlRequest=new CurlRequest($url);
    $curlSender=new CurlSender();
    $curlSender->options['USERPWD']='test:test';//TODO LM credentials!!!
    $curlRequest->setSender($curlSender);
    return $curlRequest;
  }




  #endregion kód z KBI ===============================================================================================

  #region constructor
  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param $params = array()
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade, RulesFacade $rulesFacade, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
    $this->params=$params;
    $this->rulesFacade=$rulesFacade;
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


}