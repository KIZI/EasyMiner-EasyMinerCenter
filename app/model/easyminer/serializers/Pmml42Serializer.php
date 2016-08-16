<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;

/**
 * Class Pmml42Serializer - třída pro serializaci výsledků DM úlohy do standartního PMML (model AssociationRules) ve verzi 4.2
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 *
 * @author Stanislav Vojíř
 */
class Pmml42Serializer{
  use PmmlSerializerTrait;

  /** @var DatabaseFactory $databaseFactory */
  private $databaseFactory;
  /** @var PreprocessingFactory $preprocessingFactory */
  private $preprocessingFactory;

  /** @var \SimpleXMLElement $pmml */
  private $pmml;
  /** @var  \SimpleXMLElement $associationModelXml */
  private $associationModelXml;
  /** @var  Task $task */
  protected $task;
  /** @var  Miner $miner */
  protected $miner;
  /** @var string $appVersion */
  public $appVersion='';

  /** @var  array $item */
  private $serializedRuleAttributesArr;
  /** @var  array $cedentsXmlDataArr */
  private $cedentsXmlDataArr;
  /** @var  array $rulesXmlArr */
  private $rulesXmlDataArr;


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
    //připojení info do hlavičky PMML
    $this->appendTaskInfo();

    $this->databaseFactory=$databaseFactory;
    $this->preprocessingFactory=$preprocessingFactory;
  }

  /**
   * Funkce pro připojení informace o datasetu
   * @param \SimpleXMLElement|null $datasetExtension
   */
  protected function appendDatasetInfo(\SimpleXMLElement $datasetExtension){
    if (!empty($datasetExtension)){
      $datasetExtension['value']=$this->miner->metasource->ppDatasetId;
    }else{
      $this->addExtensionElement($header,'dataset',$this->miner->metasource->ppDatasetId);
    }
  }

  /**
   * Funkce pro připojení kompletního modelu asociačních pravidel do PMML
   */
  public function appendRules(){
    /** @var \SimpleXMLElement $associationModel*/
    $this->associationModelXml=$this->pmml->AssociationModel;
    $this->associationModelXml['numberOfTransactions']=$this->miner->metasource->size;

    $this->serializedRuleAttributesArr=[];
    $this->cedentsXmlDataArr=[];
    $this->rulesXmlDataArr=[];

    if($this->task->rulesCount>0){
      foreach($this->task->rules as $rule){
        $this->serializeRule($rule);
      }
    }

    //xml z dat itemsetů
    if (!empty($this->cedentsXmlDataArr)){
      foreach($this->cedentsXmlDataArr as $cedentId=>$cedentXmlData){
        $itemsetXml=$this->associationModelXml->addChild('Itemset');
        $itemsetXml->addAttribute('id','c'.$cedentId);
        $itemsetXml->addAttribute('numberOfItems',count($cedentXmlData));
        foreach($cedentXmlData as $itemId){
          $itemsetXml->addChild('ItemRef','i'.$itemId);
        }
      }
    }
    $this->cedentsXmlDataArr=[];

    //xml z dat pravidel
    if (!empty($this->rulesXmlDataArr)){
      $this->associationModelXml['numberOfRules']=count($this->rulesXmlDataArr);
      foreach($this->rulesXmlDataArr as &$ruleXmlData){
        $associationRuleXml=$this->associationModelXml->addChild('AssociationRule');
        $associationRuleXml->addAttribute('id',$ruleXmlData['id']);
        if (isset($ruleXmlData['antecedent'])){
          $associationRuleXml->addAttribute('antecedent',$ruleXmlData['antecedent']);
        }
        $associationRuleXml->addAttribute('consequent',$ruleXmlData['consequent']);
        $associationRuleXml->addAttribute('confidence',$ruleXmlData['confidence']);
        $associationRuleXml->addAttribute('support',$ruleXmlData['support']);
        $associationRuleXml->addAttribute('lift',$ruleXmlData['lift']);
        $this->addExtensionElement($associationRuleXml,'a',$ruleXmlData['a'],null,false);
        $this->addExtensionElement($associationRuleXml,'b',$ruleXmlData['b'],null,false);
        $this->addExtensionElement($associationRuleXml,'c',$ruleXmlData['c'],null,false);
        $this->addExtensionElement($associationRuleXml,'d',$ruleXmlData['d'],null,false);
        if (isset($ruleXmlData['marked'])){
          $this->addExtensionElement($associationRuleXml,'mark',$ruleXmlData['marked']);
        }
        unset($ruleXmlData);
      }
    }
    $this->rulesXmlDataArr=[];

    $this->associationModelXml['numberOfItems']=count($this->associationModelXml->Item);
    $this->associationModelXml['numberOfItemsets']=count($this->associationModelXml->Itemset);
  }

  /**
   * Funkce pro serializaci asociačního pravidla
   * @param Rule $rule
   */
  private function serializeRule(Rule $rule){
    $ruleXmlData=[];
    //antecedent
    if ($rule->antecedent){
      $cedentId=$rule->antecedent->cedentId;
      if (!isset($this->cedentsXmlDataArr[$cedentId])){
        $this->serializeCedent($rule->antecedent);
      }
      $ruleXmlData['antecedent']='c'.$cedentId;
    }
    //consequent
    $cedentId=$rule->consequent->cedentId;
    if (!isset($this->cedentsXmlDataArr[$cedentId])){
      $this->serializeCedent($rule->consequent);
    }
    $ruleXmlData['consequent']='c'.$cedentId;
    //ID
    $ruleXmlData['id']=$rule->ruleId;
    //IMs
    $ruleXmlData['confidence']=$rule->confidence;
    $ruleXmlData['support']=$rule->support;
    $ruleXmlData['lift']=$rule->lift;
    $ruleXmlData['a']=$rule->a;
    $ruleXmlData['b']=$rule->b;
    $ruleXmlData['c']=$rule->c;
    $ruleXmlData['d']=$rule->d;
    if($rule->inRuleClipboard){
      $ruleXmlData['marked']='interesting';
    }
    $this->rulesXmlDataArr[]=$ruleXmlData;
  }

  /**
   * Funkce pro serializaci jednoho cedentu
   * @param Cedent $cedent
   */
  private function serializeCedent(Cedent $cedent){
    $ruleAttributes=$cedent->ruleAttributes;
    $dataArr=[];
    if(!empty($ruleAttributes)){
      foreach($ruleAttributes as $ruleAttribute){
        $this->serializeRuleAttribute($ruleAttribute);
        $dataArr[]=$ruleAttribute->ruleAttributeId;
      }
    }
    $this->cedentsXmlDataArr[$cedent->cedentId]=$dataArr;
  }

  /**
   * Funkce pro serializaci atributu v asociačním pravidle (jde o item v associationmodelu)
   * @param RuleAttribute $ruleAttribute
   */
  private function serializeRuleAttribute(RuleAttribute $ruleAttribute){
    $itemXml=$this->associationModelXml->addChild('Item');
    $itemXml->addAttribute('id','i'.$ruleAttribute->ruleAttributeId);
    $this->addExtensionElement($itemXml,'field',(string)$ruleAttribute->attribute->name,null,false);
    $valuesArr=[];
    if ($ruleAttribute->valuesBin){
      if($ruleAttribute->valuesBin->values){
        foreach($ruleAttribute->valuesBin->values as $value){
          $value=(string)$value;
          $valuesArr[]=$value;
          $this->addExtensionElement($itemXml,'value',$value,null,false);
        }
      }
      if($ruleAttribute->valuesBin->intervals){
        foreach($ruleAttribute->valuesBin->intervals as $interval){
          $value=(string)$interval;
          $valuesArr[]=$value;
          $this->addExtensionElement($itemXml,'value',$value);
        }
      }
    }elseif($ruleAttribute->value){
      $value=(string)$ruleAttribute->value;
      $valuesArr[]=$value;
      $this->addExtensionElement($itemXml,'value',$value,null,false);
    }
    $itemXml->addAttribute('value',$ruleAttribute->attribute->name.'='.implode(',',$valuesArr));
  }

  private function appendTaskSettings(){
    //TODO minimumSupport="" minimumConfidence="" isScorable="true|false"
  }



  /**
   * Funkce pro připravení základu prázdného PMML dokumentu pro záznam modelu AssociationModel
   */
  private function prepareBlankPmml(){
    $this->pmml=simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
<PMML xmlns="http://www.dmg.org/PMML-4_2" version="4.2">
    <Header>
      <Extension name="author" value=""/>
      <Extension name="subsystem" value=""/>
      <Extension name="module" value=""/>
      <Extension name="format" value="AssociationModel"/>
      <Extension name="dataset" value="" />
      <Application name="EasyMiner" version=""/>
      <Timestamp></Timestamp>
    </Header>
    <DataDictionary numberOfFields="2">
      <DataField name="transaction" optype="categorical" dataType="string"/>
      <DataField name="item" optype="categorical" dataType="string"/>
    </DataDictionary>
    <TransformationDictionary />
    <AssociationModel functionName="associationRules">
      <MiningSchema>
        <MiningField name="transaction" usageType="group"/>
        <MiningField name="item" usageType="active"/>
      </MiningSchema>
    </AssociationModel>
</PMML>');
  }

  /**
   * @return \SimpleXMLElement
   */
  public function getPmml(){
    return $this->pmml;
  }

}