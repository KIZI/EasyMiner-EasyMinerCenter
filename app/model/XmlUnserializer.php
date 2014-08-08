<?php

namespace App\Model;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Value;
use App\Model\Rdf\Entities\ValuesBin;
use Nette\Object;

/**
 * Class XmlUnserializer - třída pro unserializaci entit z XML do podoby objektů
 * Třída je součástí Facade pro ukládání entit v závislosti na znalostní bázi
 * @package App\Model
 */
class XmlUnserializer extends Object{

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


#region pravidla
} 