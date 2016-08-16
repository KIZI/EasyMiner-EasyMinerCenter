<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;
use Nette\Utils\Strings;

/**
 * Trait PmmlSerializerTrait obsahuje metody sdílené všemi serializacemi do PMML modelů
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
trait PmmlSerializerTrait{

  protected $dataTypesTransformationArr=[
    'int'=>'integer',
    'float'=>'float',
    'string'=>'string',
    'nominal'=>'string',
    'numeric'=>'float'
  ];

  /**
   * Funkce pro přidání tagu <Extension name="..." value="..." />
   *
   * @param \SimpleXMLElement &$parentSimpleXmlElement
   * @param string $extensionName
   * @param string $extensionValue
   * @param string|null $extensionExtender
   */
  protected function addExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender=null, $groupExtensions=true){
    if ($groupExtensions && count($parentSimpleXmlElement->Extension)>0){//TODO tohle nefunguje v rámci nového serveru...
      /** @noinspection PhpUndefinedFieldInspection */
      $siblinkElement = $parentSimpleXmlElement->Extension[0];
      $siblinkElementDom=dom_import_simplexml($siblinkElement);
      //připravení elementu pro připojení
      $extensionElement=new \SimpleXMLElement('<Extension />');
      $extensionElement->addAttribute('name',$extensionName);
      $extensionElement->addAttribute('value',$extensionValue);
      if ($extensionExtender!==null){
        $extensionElement->addAttribute('extender',$extensionExtender);
      }
      $extensionElementDom = $siblinkElementDom->ownerDocument->importNode(dom_import_simplexml($extensionElement), true);
      $siblinkElementDom->parentNode->insertBefore($extensionElementDom, $siblinkElementDom);
    }else{
      $extensionElement=$parentSimpleXmlElement->addChild('Extension');
      $extensionElement->addAttribute('name',$extensionName);
      $extensionElement->addAttribute('value',$extensionValue);
      if ($extensionExtender!==null){
        $extensionElement->addAttribute('extender',$extensionExtender);
      }
    }
  }

  /**
   * @param \SimpleXMLElement $parentSimpleXmlElement
   * @param string $extensionName
   * @param string $extensionValue
   * @param string|null $extensionExtender = null
   * @param bool $groupExtensions = true
   */
  protected function setExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender=null, $groupExtensions=true){
    if ($extensionElement=$this->getExtensionElement($parentSimpleXmlElement,$extensionName)){
      //existuje příslušný element Extension
      $extensionElement['name']=$extensionName;
      $extensionElement['value']=$extensionValue;
      if ($extensionExtender!=''){
        $extensionElement['extender']=$extensionExtender;
      }else{
        unset($extensionExtender['extender']);
      }
    }else{
      $this->addExtensionElement($parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender,$groupExtensions);
    }
  }

  /**
   * Funkce vracející konkrétní extension
   * @param \SimpleXMLElement $parentSimpleXmlElement
   * @param string $extensionName
   * @return \SimpleXMLElement|null
   */
  protected function getExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement, $extensionName){
    /** @noinspection PhpUndefinedFieldInspection */
    if (count($parentSimpleXmlElement->Extension)>0){
      /** @noinspection PhpUndefinedFieldInspection */
      foreach($parentSimpleXmlElement->Extension as $extension){
        if (@$extension['name']==$extensionName){
          return $extension;
        }
      }
    }
    return null;
  }

  /**
   * Funkce pro nastavení základních informací o úloze, ke které je vytvářena serializace
   */
  private function appendTaskInfo() {
    /** @var \SimpleXMLElement $headerXml */
    $headerXml=$this->pmml->Header;
    if ($this->task->type==Miner::TYPE_LM){
      //lispminer
      $this->setExtensionElement($headerXml,'subsystem','4ft-Miner');
      $this->setExtensionElement($headerXml,'module','LMConnect');
    }elseif($this->task->type==Miner::TYPE_R){
      //R
      $this->setExtensionElement($headerXml,'subsystem','R');
      $this->setExtensionElement($headerXml,'module','Apriori-R');
    }else{
      //other
      $this->setExtensionElement($headerXml,'subsystem',$this->task->type);
      $this->setExtensionElement($headerXml,'module',$this->task->type);
    }
    //základní informace o autorovi a timestamp
    $this->setExtensionElement($headerXml,'author',(!empty($this->miner->user)?$this->miner->user->name:''));
    $headerXml->Timestamp=date('Y-m-d H:i:s').' GMT '.str_replace(['+','-'],['+ ','- '],date('P'));
    $applicationXml=$headerXml->Application;
    $applicationXml['name']='EasyMiner';
    $applicationXml['version']=$this->appVersion;
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
   * @throws \Exception
   */
  public function appendTransformationDictionary($includeFrequencies=true){
    $metasource=$this->miner->metasource;
    if (!$this->preprocessingFactory instanceof PreprocessingFactory){
      throw new \Exception('Preprocessing factory is not configured properly.');
    }
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
            if ($ppValue->value==''){
              continue;//ignorujeme prázdné hodnoty
            }
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

  protected function appendAssociationModelTaskSettings(){
    $taskSettingsSerializer=new AssociationModelTaskSettingsSerializer($this->pmml);
    /** @var Task $task */
    $task=$this->task;
    $taskSettingsSerializer->settingsFromJson($task->getTaskSettings());
  }
}