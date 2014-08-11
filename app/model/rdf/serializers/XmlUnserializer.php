<?php

namespace App\Model\Rdf\Serializers;

use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\Cedent;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\KnowledgeBase;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
use App\Model\Rdf\Entities\RuleAttribute;
use App\Model\Rdf\Entities\RuleSet;
use App\Model\Rdf\Entities\Value;
use App\Model\Rdf\Entities\ValuesBin;
use App\Model\Rdf\Repositories\KnowledgeRepository;
use Nette\Object;

/**
 * Class XmlUnserializer - třída pro unserializaci entit z XML do podoby objektů
 * Třída je součástí Facade pro ukládání entit v závislosti na znalostní bázi
 * @package App\Model
 */
class XmlUnserializer extends Object{
  /** @var \App\Model\Rdf\Repositories\KnowledgeRepository $knowledgeRepository */
  private $knowledgeRepository;

  public function __construct(KnowledgeRepository $knowledgeRepository){
    $this->knowledgeRepository=$knowledgeRepository;
  }

  #region příprava XML (s validací)
  public static function prepareRulesXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareRuleSetXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareMetaAttributesXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareMetaAttributeXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareAttributesXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareAttributeXml($xmlString){
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }

  public static function prepareKnowledgeBaseXml($xmlString) {
    //TODO validace oproti schématu
    return simplexml_load_string($xmlString);
  }
  #endregion příprava XML (s validací)

#region metaatributy
  /**
   * Funkce pro vygenerování struktury metaatributu na základě
   * @param \SimpleXMLElement $metaAttributeXml
   * @return MetaAttribute
   */
  public function metaAttributeFromXml(\SimpleXMLElement $metaAttributeXml){
    $metaAttribute=new MetaAttribute();
    $metaAttribute->uri=(string)$metaAttributeXml['id'];
    $metaAttribute->name=(string)$metaAttributeXml->Name;

    //zpracujeme formáty
    if (isset($metaAttributeXml->Formats) && count($metaAttributeXml->Formats->Format)){
      $metaAttribute->formats=array();
      foreach ($metaAttributeXml->Formats->Format as $formatXml){
        $format=$this->formatFromXml($formatXml);
        $format->metaAttribute=$metaAttribute;
        $metaAttribute->formats[]=$format;
      }
    }

    return $metaAttribute;
  }

  /**
   * @param \SimpleXMLElement $formatXml
   * @return Format
   */
  private function formatFromXml(\SimpleXMLElement $formatXml){
    $format = new Format();
    $format->uri=(string)$formatXml['id'];
    $format->name=(string)$formatXml->Name;
    $format->dataType=(string)$formatXml->DataType;
    $this->rangeFromXml($formatXml->Range,$format);
    if (isset($formatXml->ValuesBins) && count($formatXml->ValuesBins->Bin)){
      $format->valuesBins=array();
      foreach ($formatXml->ValuesBins->Bin as $valuesBinXml){
        $valuesBin=$this->valuesBinFromXml($valuesBinXml);
        $format->valuesBins[]=$valuesBin;
      }
    }
    return $format;
  }

  private function valuesBinFromXml(\SimpleXMLElement $valuesBinXml){
    $valuesBin=new ValuesBin();
    $valuesBin->uri=(string)$valuesBinXml['id'];
    $valuesBin->name=(string)$valuesBinXml->Name;
    $this->rangeFromXml($valuesBinXml,$valuesBin);
    return $valuesBin;
  }

  private function rangeFromXml(\SimpleXMLElement $rangeXml,&$parentObject){
    if (!isset($parentObject->values)){
      $parentObject->values=array();
    }
    if (!isset($parentObject->intervals)){
      $parentObject->intervals=array();
    }
    if (count($rangeXml->Value)){
      foreach ($rangeXml->Value as $valueXml){
        $value=new Value();
        $value->uri=(string)$valueXml['id'];
        $value->value=(string)$valueXml;
        $parentObject->values[]=$value;
      }
    }
    if (count($rangeXml->Interval)){
      foreach ($rangeXml->Interval as $intervalXml){
        $interval=new Interval();
        $interval->uri=(string)$intervalXml['id'];
        $interval->leftMargin=(string)$intervalXml['leftMargin'];
        $interval->rightMargin=(string)$intervalXml['rightMargin'];
        $interval->closure=(string)$intervalXml['closure'];
        $parentObject->intervals[]=$interval;
      }
    }
  }
#endregion pravidla
  /**
   * Funkce pro vytvoření objektové struktury pravidla na základě XML
   * @param \SimpleXMLElement $ruleXml
   * @return Rule
   */
  public function ruleFromXml(\SimpleXMLElement $ruleXml){
    $rule = new Rule();
    $rule->uri=(string)$ruleXml['id'];
    $rule->text=(string)$ruleXml->Text;
    $rule->antecedent=$this->cedentFromXml($ruleXml->Antecedent);
    $rule->consequent=$this->cedentFromXml($ruleXml->Consequent);
    return $rule;
  }

  public function cedentFromXml(\SimpleXMLElement $cedentXml){
    $cedent=new Cedent();
    $cedent->uri=$cedentXml['id'];
    if (!empty($cedentXml['connective'])){
      $cedent->connective=(string)$cedentXml['connective'];
    }else{
      $cedent->connective='Conjunction';
    }
    if (count($cedentXml->Cedent)){
      $cedent->cedents=array();
      foreach ($cedentXml->Cedent as $subCedentXml){
        $cedent->cedents[]=$this->cedentFromXml($subCedentXml);
      }
    }
    if (count($cedentXml->RuleAttribute)){
      $cedent->ruleAttributes=array();
      foreach ($cedentXml->RuleAttribute as $ruleAttributeXml){
        $ruleAttribute=new RuleAttribute();
        $ruleAttribute->uri=(string)$ruleAttributeXml['id'];
        $attribute=$this->knowledgeRepository->findAttribute((string)$ruleAttributeXml['attribute']);
        //TODO pokud atribut neexistuje, je nutné vyhodit chybu!
        $ruleAttribute->attribute=$attribute;
        if (count($ruleAttributeXml->Bin)){
          foreach($ruleAttributeXml->Bin as $binXml){
            if (!empty($binXml['id'])){
              $ruleAttribute->valuesBins[]=$this->knowledgeRepository->findValuesBin((string)$binXml['id']);
            }else{
              //budeme vytvářet nový BIN, respektive hledat BIN, který má stejné parametry
              //TODO check existing bin
              //TODO check values and intervals in Attribute Range!
              $attributeValuesBins=$attribute->valuesBins;
              $valuesBin=$this->valuesBinFromXml($binXml);
              $ruleAttribute->valuesBins[]=$valuesBin;
              //zkontrolujeme, jestli je daný valuesBin také v definici atributu
              if ($valuesBin->uri){
                $valuesBinUrisArr=array();
                if (count($attributeValuesBins)){
                  foreach ($attributeValuesBins as $attributeValuesBinItem){
                    $valuesBinUrisArr[]=$attributeValuesBinItem->uri;
                  }
                }
                if (!in_array($valuesBin->uri,$valuesBinUrisArr)){
                  $attribute->valuesBins[]=$valuesBin;
                }
              }else{
                $attribute->valuesBins[]=$valuesBin;
              }
            }
          }
        }
        $cedent->ruleAttributes[]=$ruleAttribute;
      }
    }
    return $cedent;
  }
#region pravidla
#region preprocessings
//TODO preprocessings!
#endregion
#region ruleSets
  /**
   * Funkce pro vytvoření objektové struktury rulesetu na základě XML
   * @param \SimpleXMLElement $ruleSetXml
   * @return RuleSet
   */
  public function ruleSetFromXml(\SimpleXMLElement $ruleSetXml){
    $ruleSet = new RuleSet();
    $ruleSet->uri=(string)$ruleSetXml['id'];
    $ruleSet->name=(string)$ruleSetXml->Name;

    if (isset($ruleSetXml->Rules)){
      $ruleSet->rules=array();
      if (count($ruleSetXml->Rules->Rule)){
        foreach ($ruleSetXml->Rules->Rule as $ruleXml){
          $rule=$this->ruleFromXml($ruleXml);//zpětnou vazbu přiřazovat nebudeme, abychom nezrušili případné propojení pravidla s dalšími rulesety
          $ruleSet->rules[]=$rule;
        }
      }
    }

    return $ruleSet;
  }
#endregion ruleSets
#region knowledgeBases
  /**
   * Funkce pro vytvoření objektové struktury knowledgeBase na základě XML
   * @param \SimpleXMLElement $knowledgeBaseXml
   * @return RuleSet
   */
  public function knowledgeBaseFromXml(\SimpleXMLElement $knowledgeBaseXml){
    $knowledgeBase = new KnowledgeBase();
    $knowledgeBase->uri=(string)$knowledgeBaseXml['id'];
    $knowledgeBase->name=(string)$knowledgeBaseXml->Name;

    return $knowledgeBase;
  }

#endregion knowledgeBases

#region attributes
  /**
   * Funkce pro vygenerování struktury atributu na základě XML
   * @param \SimpleXMLElement $attributeXml
   * @return Attribute
   */
  public function attributeFromXml(\SimpleXMLElement $attributeXml){
    $attribute=new Attribute();
    $attribute->uri=(string)$attributeXml['id'];
    $attribute->name=(string)$attributeXml->Name;

    if (@$attributeXml['format']!=''){
      $attribute->format=$this->knowledgeRepository->findFormat((string)$attributeXml['format']);
    }

    if (@$attributeXml['preprocessing']!=''){
      $attribute->preprocessing=$this->knowledgeRepository->findPreprocessing((string)$attributeXml['preprocessing']);
    }

    //zpracujeme valuesBins
    if (isset($attributeXml->ValuesBins) && count($attributeXml->ValuesBins->Bin)){
      $attribute->valuesBins=array();
      foreach ($attributeXml->ValuesBins->Bin as $valuesBinXml){
        $valuesBin=$this->valuesBinFromXml($valuesBinXml);
        $attribute->valuesBins[]=$valuesBin;
      }
    }

    return $attribute;
  }

#endregion attributes


} 