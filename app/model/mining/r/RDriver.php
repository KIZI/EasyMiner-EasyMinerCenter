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
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\TaskSettingsSerializer;
use EasyMinerCenter\Model\Mining\IMiningDriver;
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
  /** @var MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var array $params - parametry výchozí konfigurace */
  private $params;

  #region konstanty pro dolování (před vyhodnocením pokusu o dolování jako chyby)
  const MAX_MINING_REQUESTS=5;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @return TaskState
   * @throws \Exception
   */
  public function startMining() {
    $pmmlSerializer=new GuhaPmmlSerializer($this->task);
    $pmmlSerializer->appendMetabaseInfo();
    $taskSettingsSerializer=new TaskSettingsSerializer($pmmlSerializer->getPmml());
    $taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
    $pmml=$taskSettingsSerializer->getPmml();
    //import úlohy a spuštění dolování...
    //exit($pmml->asXML());
    $numRequests=1;
    sendStartRequest:
    try{
      #region pracovní zjednodušený request
      $response=$this->curlRequestResponse($this->getRemoteMinerUrl().'/mine', $pmml->asXML());
      $taskState=$this->parseResponse($response);
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
          $response=$this->curlRequestResponse($this->getRemoteServerUrl().$this->task->resultsUrl);
          return $this->parseResponse($response);
          #endregion
        }catch (\Exception $e){
          if ((++$numRequests < self::MAX_MINING_REQUESTS)){sleep(self::REQUEST_DELAY); goto sendStartRequest;}
        }
      }else{
        $taskState=$this->task->taskState;
        $taskState->state=Task::STATE_FAILED;
      }
    }elseif ($this->task->state==Task::STATE_SOLVED_HEADS){
      if (file_exists($this->getImportPmmlPath())){
        //ignore user abort (continue running this script)
        ignore_user_abort(true);
        //update task details
        $rulesCount=0;
        $this->fullParseRulesPMML(simplexml_load_file($this->getImportPmmlPath()),$rulesCount,true);
        if ($rulesCount!=$this->task->rulesCount){
          $this->task->rulesCount=$rulesCount;
        }
        return new TaskState(Task::STATE_SOLVED,$this->task->rulesCount);
      }
    }
    return $this->task->taskState;
  }

  /**
   * Funkce pro zastavení dolování
   * @return TaskState
   */
  public function stopMining() {
    $taskState=$this->task->taskState;
    if ($taskState==Task::STATE_IN_PROGRESS){
      $taskState->state=Task::STATE_INTERRUPTED;
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
  public function checkMinerState(){
    return true;
  }

  /**
   * Funkce vracející adresu R connectu
   * @return string
   */
  private function getRemoteMinerUrl(){
    return $this->getRemoteServerUrl().@$this->params['minerUrl'];
  }

  /**
   * Funkce vracející adresu serveru, kde běží R connect
   * @return string
   */
  private function getRemoteServerUrl(){
    return @$this->params['server'];
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
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - informace o počtu importovaných pravidel
   * @param string $responseData
   * @return bool
   * @throws \Exception
   */
  public function parseRulesPMML($pmml,&$rulesCount=null,$responseData){
    if (is_dir($this->params['importsDirectory'])){
      file_put_contents($this->getImportPmmlPath(),$responseData);
      $this->basicParseRulesPMML($pmml,$rulesCount);
      return Task::STATE_SOLVED_HEADS;
    }else{
      $this->fullParseRulesPMML($pmml,$rulesCount);
      @unlink($this->getImportPmmlPath());
      return Task::STATE_SOLVED;
    }
  }

  /**
   * Funkce pro načtení PMML
   * @param string|\SimpleXMLElement $pmml
   * @param int &$rulesCount = null - informace o počtu importovaných pravidel
   * @return bool
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

    if (empty($associationRules)){return true;}//pokud nejsou vrácena žádná pravidla, nemá smysl zpracovávat cedenty...

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
    return true;
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
   * @param string $message
   * @return TaskState
   * @throws \Exception
   */
  private function parseResponse($response, $message = ''){
    $body=simplexml_load_string($response);
    if ($body->getName()=='status' || $body->getName()=='error'){
      //jde o informaci o stavu minování
      $code=substr($body->code,0,3);
      switch ($code){
        case '202':
          //jde o informaci o tom, že je nutné na odpověď dál čekat
          $this->task->state=Task::STATE_IN_PROGRESS;
          $resultUrl=(string)$body->miner->{'result-url'};
          if ($resultUrl=='' && $this->task->resultUrl!=''){
            $resultUrl=$this->task->resultUrl;
          }
          return new TaskState(Task::STATE_IN_PROGRESS,null,$resultUrl);
        case '500':
          //jde o chybu mineru
          return new TaskState(Task::STATE_FAILED);
      }
    }elseif($body->getName()=='PMML'){
      //jde o export výsledků
      $rulesCount=0;
      $taskState=$this->parseRulesPMML($body,$rulesCount,$response);
      return new TaskState($taskState,$rulesCount);
    }else{
      throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
    }
    return new TaskState(Task::STATE_FAILED);
  }


  #region constructor
  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param $params = array()
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
    $this->params=$params;
    $this->rulesFacade=$rulesFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
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
   * @param string $postData
   * @return string - response data
   * @throws \Exception - curl error
   */
  private function curlRequestResponse($url,$postData=''){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    $headersArr=array('Content-Type: application/xml; charset=utf-8');
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

  /**
   * @return string
   */
  private function getImportPmmlPath(){
    return $this->params['importsDirectory'].'/'.$this->task->taskId.'.pmml';
  }



}