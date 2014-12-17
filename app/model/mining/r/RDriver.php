<?php
namespace App\Model\Mining\R;

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
use Kdyby\Curl\Request;
use Kdyby\Curl\Response as CurlResponse;
use Nette\Utils\Strings;

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
  /** @var array $params - parametry výchozí konfigurace */
  private $params;

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
    $pmmlSerializer->appendMetabaseInfo();
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
    //import úlohy a spuštění dolování...
    $numRequests=1;
    sendRequest:
    //try{
    #region pracovní zjednodušený request
    $ch = curl_init($this->getRemoteMinerUrl().'/mine');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $pmml->asXML());
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml","Content-length: ".strlen($pmml->asXML())));

    $tuData = curl_exec($ch);
    if(!curl_errno($ch)){
      $info = curl_getinfo($ch);
      echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
    } else {
      echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);
    echo $tuData;
exit('-----');

    #endregion

      $request=$this->prepareNewCurlRequest($this->getRemoteMinerUrl().'/mine');
      $request->followRedirects=false;
    $request->maximumRedirects=0;
    $request->setCertificationVerify(false);
      $request->returnTransfer=false;
      $request->headers['Content-Type']='application/xml';
      $response = $request->post($pmml->asXML());

    //}catch (\Exception $e){
    //  exit($e->getMessage());
    //}
    exit(var_dump($response));

    //exit(var_dump($response->headers));


    $result=$this->queryPost($pmml);
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
    //TODO
  }

  /**
   * Funkce pro zastavení dolování
   * @return TaskState
   */
  public function stopMining() {
    return false;//TODO
  }

  /**
   * @param string $result
   * @return TaskState
   * @throws \Exception
   */
  private function parseTaskState($result){//TODO
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
   */
  public function importResults(){
    //TODO
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
  }

  /**
   * Funkce volaná před smazáním konkrétního mineru
   * @return mixed|false
   */
  public function deleteMiner() {
    return true;
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @throws \Exception
   */
  public function checkMinerState(){
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
<?oxygen SCHSchema="http://sewebar.vse.cz/schemas/GUHARestr0_1.sch"?>
<PMML version="4.0" xmlns="http://www.dmg.org/PMML-4_0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:pmml="http://www.dmg.org/PMML-4_0" xsi:schemaLocation="http://www.dmg.org/PMML-4_0 http://sewebar.vse.cz/schemas/PMML4.0+GUHA0.1.xsd">
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
  private function setMinerConfig($minerConfig,$save=true){//TODO
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
    return new CurlRequest($url);

    $curlRequest=new CurlRequest($url);
    $curlSender=new CurlSender();
    $curlSender->setFollowRedirects(false);
    $curlRequest->setSender($curlSender);
    return $curlRequest;
  }

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