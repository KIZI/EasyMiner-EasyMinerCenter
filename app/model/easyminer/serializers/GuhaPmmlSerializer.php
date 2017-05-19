<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;
use Nette\Utils\Strings;

/**
 * Class GuhaPmmlSerializer - class for building of a GUHA PMML file from a Task
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class GuhaPmmlSerializer {
  use PmmlSerializerTrait;

  /** @var  \SimpleXMLElement $pmml */
  protected $pmml;
  /** @var  Task $task */
  protected $task;
  /** @var  Miner $miner */
  protected $miner;
  /** @var string $appVersion */
  public $appVersion='';

  const PMML_XMLNS='http://www.dmg.org/PMML-4_0';

  /** @var  DatabaseFactory $databaseFactory */
  protected $databaseFactory;
  /** @var PreprocessingFactory $preprocessingFactory */
  protected $preprocessingFactory;

  /** @var  \SimpleXMLElement $BBAsWorkXml */
  protected $BBAsWorkXml;
  /** @var  \SimpleXMLElement $DBAsWorkXml */
  protected $DBAsWorkXml;
  /** @var  \SimpleXMLElement $associationRulesWorkXml */
  protected $associationRulesWorkXml;
  /** @var int[] $serializedCedents array with indexes of partial cedents, which should be serialized */
  protected $serializedCedentsArr;
  /** @var int[] $serializedRuleAttributesArr array with indexes of partial ruleAttributes, which should be serialized */
  protected $serializedRuleAttributesArr;
  /** @var  array $connectivesArr array with translations of connectives for serialization */
  protected $connectivesArr;

  /**
   * @return \SimpleXMLElement
   */
  public function getPmml(){
    return $this->pmml;
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|null $pmml
   * @param DatabaseFactory $databaseFactory
   * @param PreprocessingFactory $preprocessingFactory
   * @param string $appVersion =''
   */
  public function __construct(Task $task, \SimpleXMLElement $pmml = null, DatabaseFactory $databaseFactory, PreprocessingFactory $preprocessingFactory, $appVersion=''){
    if ($task instanceof Task){
      $this->task=$task;
      $this->miner=$task->miner;
    }
    $this->appVersion=$appVersion;
    if (!empty($pmml)){
      if ($pmml instanceof \SimpleXMLElement){
        $this->pmml=$pmml;
      }elseif(is_string($pmml)){
        $this->pmml=simplexml_load_string($pmml);
      }
    }
    if (!$pmml instanceof \SimpleXMLElement){
      $this->prepareBlankPmml();
    }

    $this->appendTaskInfo();

    $this->databaseFactory=$databaseFactory;
    $this->preprocessingFactory=$preprocessingFactory;

    $connectivesArr=Cedent::getConnectives();
    foreach($connectivesArr as $connective){
      $this->connectivesArr[$connective]=Strings::firstUpper($connective);
    }
  }

  /**
   * Method for appending of basic info about connection to database to PMML file
   */
  public function appendMetabaseInfo() {
    /** @var \SimpleXMLElement $headerXml */
    $headerXml=$this->pmml->Header;
    $dbConnection=$this->miner->metasource->getDbConnection();//TODO přesunutí ve struktuře PMML - kvůli validitě
    $this->addExtensionElement($headerXml,'database-type',$dbConnection->type);
    $this->addExtensionElement($headerXml,'database-server',$dbConnection->dbServer);
    $this->addExtensionElement($headerXml,'database-name',$dbConnection->dbName);
    $this->addExtensionElement($headerXml,'database-user',$dbConnection->dbUsername);
    $this->addExtensionElement($headerXml,'database-password',$dbConnection->dbPassword);
  }

  /**
   * Method preparing basic structure of GUHA PMML file
   */
  protected function prepareBlankPmml(){
    $this->pmml = simplexml_load_string('<'.'?xml version="1.0" encoding="UTF-8"?>
      <'.'?oxygen SCHSchema="http://easyminer.eu/schemas/GUHARestr0_1.sch"?>
      <PMML xmlns="'.self::PMML_XMLNS.'" version="4.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:pmml="http://www.dmg.org/PMML-4_0" xsi:schemaLocation="http://www.dmg.org/PMML-4_0 http://easyminer.eu/schemas/PMML4.0+GUHA0.1.xsd">
        <Header copyright="Copyright (c) KIZI UEP, '.date('Y').'">
          <Extension name="author" value=""/>
          <Extension name="subsystem" value=""/>
          <Extension name="module" value=""/>
          <Extension name="format" value="4ftMiner.Task"/>
          <Extension name="dataset" value="" />
          <Application name="EasyMiner" version=""/>
          <Timestamp></Timestamp>
        </Header>
        <DataDictionary/>
        <TransformationDictionary/>
        <guha:AssociationModel xmlns:guha="http://keg.vse.cz/ns/GUHA0.1rev1" xmlns="" />
      </PMML>');
    /** @var \SimpleXMLElement $header */
    $header=$this->pmml->Header;
    $datasetExtension=null;
    foreach($header->Extension as $extension){
      if ($extension['name']=='dataset'){
        $datasetExtension=$extension;
        break;
      }
    }
    $this->appendDatasetInfo($datasetExtension);
  }

  /**
   * Method for appending info about used dataset
   * @param \SimpleXMLElement|null $datasetExtension
   */
  protected function appendDatasetInfo(\SimpleXMLElement $datasetExtension){
    if (!empty($datasetExtension)){
      $datasetExtension['value']=$this->miner->metasource->name;
    }else{
      $this->addExtensionElement($header,'dataset',$this->miner->metasource->name);
    }
  }

  /**
   * Method for appending info about task settings
   */
  public function appendTaskSettings(){
    $taskSettingsSerializer=new TaskSettingsSerializer($this->pmml,$this->miner->type);
    $this->pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
  }

  /**
   * Method for appending of rules
   */
  public function appendRules(){
    if (empty($this->task->rules)){return;}
    /** @var \SimpleXMLElement $guhaAssociationModelXml */
    $guhaAssociationModelXml=$this->pmml->children('guha',true)[0];
    if (count($guhaAssociationModelXml->AssociationRules)>0){
      /** @var \SimpleXMLElement $associationRulesXml */
      $associationRulesXml=$guhaAssociationModelXml->AssociationRules;
    }else{
      /** @var \SimpleXMLElement $associationRulesXml */
      $associationRulesXml=$guhaAssociationModelXml->addChild('AssociationRules',null,'');
    }
    $this->associationRulesWorkXml=new \SimpleXMLElement('<AssociationRules xmlns="" />');
    $this->BBAsWorkXml=new \SimpleXMLElement('<BBAs xmlns="" />');
    $this->DBAsWorkXml=new \SimpleXMLElement('<DBAs xmlns="" />');
    $this->serializedCedentsArr=[];
    $this->serializedRuleAttributesArr=[];

    foreach($this->task->rules as $rule){
      //add concrete rule to XML
      $associationRuleXml=$this->associationRulesWorkXml->addChild('AssociationRule',null,'');
      $associationRuleXml->addAttribute('id',$rule->ruleId);
      if ($rule->inRuleClipboard){
        $this->addExtensionElement($associationRuleXml,'mark','interesting');
      }
      $antecedent=$rule->antecedent;
      $consequent=$rule->consequent;
      if (!empty($antecedent)){
        $associationRuleXml->addAttribute('antecedent','cdnt_'.$antecedent->cedentId);
      }
      if (!empty($consequent)){
        $associationRuleXml->addAttribute('consequent','cdnt_'.$consequent->cedentId);
      }
      $associationRuleXml->addChild('Text');
      $associationRuleXml->Text=$rule->text;
      //IMValues
      $IMValueConfidence=$associationRuleXml->addChild('IMValue',$rule->support);
      $IMValueConfidence->addAttribute('name','BASE');
      $IMValueConfidence->addAttribute('type','%All');
      $IMValueConfidence=$associationRuleXml->addChild('IMValue',$rule->confidence);
      $IMValueConfidence->addAttribute('name','CONF');
      $IMValueConfidence->addAttribute('type','%All');
      //TODO reference na zadání měr zajímavosti?
      //FourFtTable
      $fourFtTableXml=$associationRuleXml->addChild('FourFtTable');
      $fourFtTableXml->addAttribute('a',$rule->a);
      $fourFtTableXml->addAttribute('b',$rule->b);
      $fourFtTableXml->addAttribute('c',$rule->c);
      $fourFtTableXml->addAttribute('d',$rule->d);
      //serialize partial cedents
      if (!empty($rule->antecedent)){
        $this->serializeCedent($rule->antecedent);
      }
      if (!empty($rule->consequent)){
        $this->serializeCedent($rule->consequent);
      }
    }
    //merge XML files
    $associationRulesDom=dom_import_simplexml($associationRulesXml);
    if (count($this->BBAsWorkXml->children())>0){
      foreach($this->BBAsWorkXml->children() as $xmlItem){
        $insertDom=$associationRulesDom->ownerDocument->importNode(dom_import_simplexml($xmlItem),true);
        $associationRulesDom->appendChild($insertDom);
      }
    }
    if (count($this->DBAsWorkXml->children())>0){
      foreach($this->DBAsWorkXml->children() as $xmlItem){
        $insertDom=$associationRulesDom->ownerDocument->importNode(dom_import_simplexml($xmlItem),true);
        $associationRulesDom->appendChild($insertDom);
      }
    }
    if (count($this->associationRulesWorkXml->children())>0){
      foreach($this->associationRulesWorkXml->children() as $xmlItem){
        $insertDom=$associationRulesDom->ownerDocument->importNode(dom_import_simplexml($xmlItem),true);
        $associationRulesDom->appendChild($insertDom);
      }
    }
  }

  /**
   * @param Cedent $cedent
   */
  private function serializeCedent(Cedent $cedent){
    //if this cedent was already serialized, skip it
    if(in_array($cedent->cedentId,$this->serializedCedentsArr)){return;}
    //serialize alone partial cedent
    $DBAXML=$this->DBAsWorkXml->addChild('DBA');
    $DBAXML->addAttribute('id','cdnt_'.$cedent->cedentId);
    $DBAXML->addAttribute('connective',$this->connectivesArr[$cedent->connective]);
    if (!empty($cedent->cedents)){
      //serialize partial cedents
      foreach($cedent->cedents as $subCedent){
        $DBAXML->addChild('BARef','cdnt_'.$subCedent->cedentId);
        $this->serializeCedent($subCedent);
      }
    }
    if (!empty($cedent->ruleAttributes)){
      //serialize ruleAttributes
      $DBAAttributesXML=$this->DBAsWorkXml->addChild('DBA');
      $DBAAttributesXML->addAttribute('id','cdnt_'.$cedent->cedentId.'_attr');
      $DBAAttributesXML->addAttribute('connective',$this->connectivesArr[Cedent::CONNECTIVE_CONJUNCTION]);
      $DBAAttributesXML->addAttribute('literal','false');
      $DBAXML->addChild('BARef','cdnt_'.$cedent->cedentId.'_attr');
      foreach($cedent->ruleAttributes as $ruleAttribute) {
        $DBAAttributesXML->addChild('BARef','dba_'.$ruleAttribute->ruleAttributeId);
        $this->serializeRuleAttribute($ruleAttribute);
      }
    }
    $this->serializedCedentsArr[]=$cedent->cedentId;
  }

  private function serializeRuleAttribute(RuleAttribute $ruleAttribute){
    if (in_array($ruleAttribute->ruleAttributeId,$this->serializedRuleAttributesArr)){return;}
    //create appropriate BBA
    $BBAXML=$this->BBAsWorkXml->addChild('BBA');
    $BBAXML->addAttribute('id','bba_'.$ruleAttribute->ruleAttributeId);
    $BBAXML->addAttribute('literal','false');
    $BBAXML->addChild('FieldRef');
    $BBAXML->FieldRef=$ruleAttribute->attribute->name;
    $BBAXML->addChild('CatRef');
    $BBAXML->CatRef=(empty($ruleAttribute->valuesBin)?$ruleAttribute->value->value:$ruleAttribute->valuesBin->name);//TODO atributy s více hodnotami!
    //
    $DBAXML=$this->DBAsWorkXml->addChild('DBA');
    $DBAXML->addAttribute('id','dba_'.$ruleAttribute->ruleAttributeId);
    $DBAXML->addAttribute('connective',$this->connectivesArr[Cedent::CONNECTIVE_CONJUNCTION]);
    $DBAXML->addAttribute('literal','true');
    $DBAXML->addChild('BARef','bba_'.$ruleAttribute->ruleAttributeId);
    //
    $this->serializedRuleAttributesArr[]=$ruleAttribute->ruleAttributeId;
  }
}