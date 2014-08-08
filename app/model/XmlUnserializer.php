<?php

namespace App\Model;
use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\Cedent;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
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
    if (isset($metaAttributeXml->formats) && count($metaAttributeXml->formats->format)){
      $metaAttribute->formats=array();
      foreach ($metaAttributeXml->formats->format as $formatXml){
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
    if (count($cedentXml->Attribute)){
      $cedent->attributes=array();
      foreach ($cedentXml->Attribute as $attributeXml){
        $attribute=new Attribute();
        $attribute->uri=(string)$attributeXml['id'];
        $attribute->format=$this->knowledgeRepository->findFormat((string)$attributeXml['format']);
        if (count($attributeXml->Bin)){
          foreach($attributeXml->Bin as $binXml){
            if (!empty($binXml['id'])){
              $attribute->valuesBins[]=$this->knowledgeRepository->findValuesBin((string)$binXml['id']);
            }else{
              //budeme vytvářet nový BIN, respektive hledat BIN, který má stejné parametry
              //TODO check existing bin
              $attribute->valuesBins[]=$this->valuesBinFromXml($binXml);
            }
          }
        }
      }
    }
    return $cedent;
  }
#region pravidla
#region preprocessings
//TODO preprocessings!
#endregion

} 