<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use EasyMinerCenter\Model\Preprocessing\IPreprocessingDriver;
use Nette\Utils\Strings;

/**
 * Class GuhaPmmlSerializer - serializer umožňující sestavit GUHA PMML dokument z dat zadané úlohy...
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
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
  /** @var int[] $serializedCedents  pole s indexy dílčích cedentů, které už byly serializovány */
  protected $serializedCedentsArr;
  /** @var int[] $serializedRuleAttributesArr  pole s indexy dílčích ruleAttributes, které už byly serializovány */
  protected $serializedRuleAttributesArr;
  /** @var  array $connectivesArr pole s překladem spojek pro serializaci */
  protected $connectivesArr;
  protected $dataTypesTransformationArr=[
    'int'=>'integer',
    'float'=>'float',
    'string'=>'string',
    'nominal'=>'string',
    'numeric'=>'float'
  ];
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
   * Funkce připojující informace o připojení k databázi do PMML souboru
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
   * Funkce připravující prázdný PMML dokument
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
   * Funkce pro připojení informace o datasetu
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
   * Funkce pro připojení informací o nastavení úlohy
   */
  public function appendTaskSettings(){
    $taskSettingsSerializer=new TaskSettingsSerializer($this->pmml,$this->miner->type);
    $this->pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
  }

  public function appendDataDictionary($includeFrequencies=true){
    $datasource=$this->miner->datasource;
    if (empty($datasource->datasourceColumns)){
      return;
    }
    /** @var \SimpleXMLElement $dataDictionaryXml */
    $dataDictionaryXml=$this->pmml->DataDictionary;
    if (!empty($dataDictionaryXml[0]['numberOfFields'])){
      $dataDictionaryXml[0]['numberOfFields']=count($datasource->datasourceColumns);
    }else{
      $dataDictionaryXml->addAttribute('numberOfFields',count($datasource->datasourceColumns));
    }
    //připojení jednotlivých data fields
    foreach($datasource->datasourceColumns as $datasourceColumn){
      $dataFieldXml=$dataDictionaryXml->addChild('DataField');
      $dataFieldXml->addAttribute('name',$datasourceColumn->name);
      $dataFieldXml->addAttribute('dataType',$this->dataTypesTransformationArr[$datasourceColumn->type]);
      if ($datasourceColumn->type==DatasourceColumn::TYPE_STRING){
        $dataFieldXml->addAttribute('optype','categorical');
      }else{
        $dataFieldXml->addAttribute('optype','continuous');
      }

      if ($includeFrequencies /*TODO*/ && false){
        //TODO replace databasesFacade
        $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$datasourceColumn->name);
        if ($datasourceColumn->type=DatasourceColumn::TYPE_STRING && !empty($valuesStatistics->valuesArr)){
          //výčet hodnot
          foreach($valuesStatistics->valuesArr as $value=>$count){
            $valueXml=$dataFieldXml->addChild('Value');
            $valueXml->addAttribute('value',$value);
            $this->addExtensionElement($valueXml,'Frequency',$count,$value);
          }
        }elseif(isset($valuesStatistics->minValue) && isset($valuesStatistics->maxValue)){
          //interval
          if ($valuesStatistics->minValue<=$valuesStatistics->maxValue){
            $this->addExtensionElement($dataFieldXml,'Avg',$valuesStatistics->avgValue,'Avg');
            $intervalXml=$dataFieldXml->addChild('Interval');
            $intervalXml->addAttribute('closure','closedClosed');
            $intervalXml->addAttribute('leftMargin',$valuesStatistics->minValue);
            $intervalXml->addAttribute('rightMargin',$valuesStatistics->maxValue);
          }
        }
      }

      //XXX TODO serializace hodnot...
    }
  }

  /**
   * Funkce pro serializaci TransformationDictionary
   * @param bool $includeFrequencies = true
   */
  public function appendTransformationDictionary($includeFrequencies=true){
    $metasource=$this->miner->metasource;
    /** @var IPreprocessing $preprocessingDriver */
    $preprocessingDriver=$this->preprocessingFactory->getPreprocessingInstance($this->miner->metasource->getPpConnection(),$this->miner->user);
    if (empty($metasource->attributes)){return;}
    /** @var \SimpleXMLElement $transformationDictionaryXml */
    $transformationDictionaryXml=$this->pmml->TransformationDictionary;
    foreach($metasource->attributes as $attribute){
      if (empty($attribute->preprocessing)){continue;}
      $derivedFieldXml=$transformationDictionaryXml->addChild('DerivedField');
      $derivedFieldXml->addAttribute('name',$attribute->name);
      $derivedFieldXml->addAttribute('dataType',$this->dataTypesTransformationArr[$attribute->type]);
      if ($attribute->type==Attribute::TYPE_STRING){
        $derivedFieldXml->addAttribute('optype','categorical');
      }else {
        $derivedFieldXml->addAttribute('optype', 'continuous');
      }
      $datasourceColumn=$attribute->datasourceColumn;

      //serializace preprocessingu
      $preprocessing=$attribute->preprocessing;
      if ($preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
        //serializace eachOne
        $mapValuesXml=$derivedFieldXml->addChild('MapValues');
        $mapValuesXml->addAttribute('outputColumn','field');
        $fieldColumnPairXml=$mapValuesXml->addChild('FieldColumnPair');
        $fieldColumnPairXml->addAttribute('column','column');
        $fieldColumnPairXml->addAttribute('field',$datasourceColumn->name);
        $inlineTableXml=$mapValuesXml->addChild('InlineTable');
        //frekvence
        $ppDataset=$preprocessingDriver->getPpDataset($metasource->ppDatasetId);
        $ppValues=$preprocessingDriver->getPpValues($ppDataset,$attribute->ppDatasetAttributeId?$attribute->ppDatasetAttributeId:$attribute->name,0,100);//TODO optimalizovat počet hodnot
        if (!empty($ppValues)){
          if ($includeFrequencies){
            foreach($ppValues as $ppValue){
              $this->addExtensionElement($inlineTableXml,'Frequency',$ppValue->frequency,$ppValue->value,false);
            }
          }
          foreach($ppValues as $ppValue){
            $rowXml=$inlineTableXml->addChild('row');
            $rowXml->addChild('column',$ppValue->value);//v původních i finálních datech je stejná hodnota
            $rowXml->addChild('field',$ppValue->value);
          }
        }
        continue;
      }
      if (empty($preprocessing->valuesBins)){continue;}
      $valuesBins=$preprocessing->valuesBins;
      if (!empty($valuesBins[0]->intervals)){
        //serializace discretizace pomocí intervalů
        $discretizeXml=$derivedFieldXml->addChild('Discretize');
        $discretizeXml->addAttribute('field',$datasourceColumn->name);
        //frekvence
        //TODO replace databasesFacade
        $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute->name);
        if (!empty($valuesStatistics->valuesArr) && $includeFrequencies){
          foreach($valuesStatistics->valuesArr as $value=>$count){
            $this->addExtensionElement($discretizeXml,'Frequency',$count,$value);
          }
        }
        foreach($valuesBins as $valuesBin){
          if (!empty($valuesBin->intervals)) {
            foreach ($valuesBin->intervals as $interval){
              if (!isset($valuesStatistics->valuesArr[$valuesBin->name])){continue;}//vynecháme neobsazené hodnoty
              $discretizeBinXml = $discretizeXml->addChild('DiscretizeBin');
              $discretizeBinXml->addAttribute('binValue', $valuesBin->name);
              $intervalXml=$discretizeBinXml->addChild('Interval');
              $closure=$interval->leftClosure.Strings::firstUpper($interval->rightClosure);
              $intervalXml->addAttribute('closure',$closure);
              $intervalXml->addAttribute('leftMargin',$interval->leftMargin);
              $intervalXml->addAttribute('rightMargin',$interval->rightMargin);
            }
          }
        }
      }elseif(!empty($valuesBins[0]->values)){
        //serializace discretizace pomocí výčtů hodnot
        $mapValuesXml=$derivedFieldXml->addChild('MapValues');
        $mapValuesXml->addAttribute('outputColumn','field');
        $fieldColumnPairXml=$mapValuesXml->addChild('FieldColumnPair');
        $fieldColumnPairXml->addAttribute('column','column');
        $fieldColumnPairXml->addAttribute('field',$datasourceColumn->name);
        $inlineTableXml=$mapValuesXml->addChild('InlineTable');
        //frekvence
        //TODO replace databasesFacade
        $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute->name);
        if (!empty($valuesStatistics->valuesArr) && $includeFrequencies){
          foreach($valuesStatistics->valuesArr as $value=>$count){
            $this->addExtensionElement($inlineTableXml,'Frequency',$count,$value);
          }
        }
        foreach($valuesBins as $valuesBin){
          if (!empty($valuesBin->values)){
            if (!isset($valuesStatistics->valuesArr[$valuesBin->name])){continue;}//vynecháme neobsazené hodnoty
            foreach ($valuesBin->values as $value){
              $rowXml=$inlineTableXml->addChild('row');
              $rowXml->addChild('column',$value->value);
              $rowXml->addChild('field',$valuesBin->name);
            }
          }
        }
      }
    }
  }

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
      //přidání konkrétního pravidla do XML
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
      //serializace dílčích cedentů
      if (!empty($rule->antecedent)){
        $this->serializeCedent($rule->antecedent);
      }
      if (!empty($rule->consequent)){
        $this->serializeCedent($rule->consequent);
      }
    }
    //sloučení XML dokumentů
    //TODO přesun do samostatné funkce?
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
    //pokud už byl cedent serializován, tak ho ignorujeme
    if(in_array($cedent->cedentId,$this->serializedCedentsArr)){return;}
    //serializace samotného dílčího cedentu
    $DBAXML=$this->DBAsWorkXml->addChild('DBA');
    $DBAXML->addAttribute('id','cdnt_'.$cedent->cedentId);
    $DBAXML->addAttribute('connective',$this->connectivesArr[$cedent->connective]);
    if (!empty($cedent->cedents)){
      //serializace dílčích cedentů
      foreach($cedent->cedents as $subCedent){
        $DBAXML->addChild('BARef','cdnt_'.$subCedent->cedentId);
        $this->serializeCedent($subCedent);
      }
    }
    if (!empty($cedent->ruleAttributes)){
      //serializace ruleAttributes
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
    //vytvoření příslušného BBA
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