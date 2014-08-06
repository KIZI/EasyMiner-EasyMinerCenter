<?php

namespace App\Model;

use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Value;

/**
 * Class XmlSerializer - třída pro serializaci entit do podoby XML
 * @package App\Model
 */
class XmlSerializer {
#region base Xml templates
  const METAATTRIBUTES_XML_BASE='<MetaAttributes xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttributes>';
  const METAATTRIBUTE_XML_BASE='<MetaAttribute xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttribute>';

  /**
   * Funkce vracecící základ XML dokumentu pro zachycení metaatributů
   * @return \SimpleXMLElement
   */
  public static function baseMetaAttributesXml(){
    return simplexml_load_string(self::METAATTRIBUTES_XML_BASE);
  }

  /**
   * Funkce vracecící základ XML dokumentu pro zachycení jednoho metaatributu
   * @return \SimpleXMLElement
   */
  public static function baseMetaAttributeXml(){
    return simplexml_load_string(self::METAATTRIBUTE_XML_BASE);
  }
#endregion

  /**
   * Funkce pro serializaci kompletního metaatributu včetně celé struktury
   * @param MetaAttribute $metaAttribute
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function metaAttributeAsXml(MetaAttribute $metaAttribute,&$parentXml=null){
    $metaAttributeXml=$this->blankMetaAttributeAsXml($metaAttribute,$parentXml);
    $formatsXml=$metaAttributeXml->addChild('Formats');
    $formats=$metaAttribute->formats;
    if (count($formats)){
      foreach ($formats as $format){
        $this->formatAsXml($format,$formatsXml);
      }
    }
    return $metaAttributeXml;
  }

  /**
   * Funkce pro serializaci metaatributu pouze se seznamem formátů (s uri a názvy, bez vnitřní struktury)
   * @param MetaAttribute $metaAttribute
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function metaAttributeWithBlankFormatsAsXml(MetaAttribute $metaAttribute,&$parentXml=null){
    $metaAttributeXml=$this->blankMetaAttributeAsXml($metaAttribute,$parentXml);
    $formatsXml=$metaAttributeXml->addChild('Formats');
    $formats=$metaAttribute->formats;
    if (count($formats)){
      foreach ($formats as $format){
        $this->blankFormatAsXml($format,$formatsXml);
      }
    }
    return $metaAttributeXml;
  }

  /**
   * Funkce pro serializaci základních info o metaatributu bez vnitřní struktury
   * @param MetaAttribute $metaAttribute
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankMetaAttributeAsXml(MetaAttribute $metaAttribute,&$parentXml=null){
    if ($parentXml instanceof \SimpleXMLElement){
      $metaAttributeXml=$parentXml->addChild('MetaAttribute');
    }else{
      $metaAttributeXml=self::baseMetaAttributeXml();
    }
    $metaAttributeXml->addAttribute('id',$metaAttribute->uri);
    $metaAttributeXml->addChild('Name',$metaAttribute->name);
    return $metaAttributeXml;
  }

  /**
   * Funkce pro serializaci základních info o formátu (bez vnitřní struktury)
   * @param Format $format
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankFormatAsXml(Format $format,&$parentXml = null){
    $formatXml=$parentXml->addChild('Format');
    $formatXml->addAttribute('id',$format->uri);
    $formatXml->addChild('Name',$format->name);
  }

  /**
   * Funkce pro serializaci formátu včetně kompletní struktury zabaleně do základní struktury metaatributu
   * @param Format $format
   * @param \SimpleXMLElement|null $parentXml
   * @return \SimpleXMLElement
   */
  public function formatInBlankMetaAttributeAsXml(Format $format,&$parentXml=null){//TODO načtení metaatributu!!!
    $metaAttributeXml=$this->blankMetaAttributeAsXml(,$parentXml);
    $formatsXml=$metaAttributeXml->addChild('Formats');
    $this->formatAsXml($format,$formatsXml);
    return $metaAttributeXml;
  }

  /**
   * Funkce pro serializaci formátu včetně kompletní struktury
   * @param Format $format
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function formatAsXml(Format $format,&$parentXml = null){
    $formatXml=$this->blankFormatAsXml($format,$parentXml);
    $formatXml->addChild('DataType',$format->dataType);
    $rangeXml=$formatXml->addChild('Range');
    //$this->rangeAsXml($rangeXml,)
    //TODO range

    $valuesBins=$format->valuesBins;
    $valuesBinsXml=$formatXml->addChild('ValuesBins');
    if (count($valuesBins)){
      foreach ($valuesBins as $valuesBin){
        $valuesBinXml=$valuesBinsXml->addChild('Bin');
        $valuesBinXml->addAttribute('id',$valuesBin->uri);
        $valuesBinXml->addChild('Name',$valuesBin->name);
        $this->rangeAsXml($valuesBinXml,$valuesBin->intervals);
        $this->rangeAsXml($valuesBinXml,$valuesBin->values);
      }
    }
    return $formatXml;
  }

  /**
   * Funkce pro serializaci rozsahu hodnot (výčet hodnot či intervalů)
   * @param \SimpleXMLElement $parentXml
   * @param Interval[]|Value[] $range
   */
  private function rangeAsXml(\SimpleXMLElement &$parentXml,$range){
    if (count($range) > 0){
      foreach ($range as $rangeItem){
        if ($rangeItem instanceof Value){
          //serializace konkrétní hodnoty
          $valueXml=$parentXml->addChild('Value',$rangeItem->value);
          if ($rangeItem->uri){
            $valueXml->addAttribute('id',$rangeItem->uri);
          }
        }elseif($rangeItem instanceof Interval){
          //serializace intervalu
          $intervalXml=$parentXml->addChild('Interval');
          $intervalXml->addAttribute('id',$rangeItem->uri);
          $intervalXml->addAttribute('closure',$rangeItem->closure);
          $intervalXml->addAttribute('leftMargin',$rangeItem->leftMargin);
          $intervalXml->addAttribute('rightMargin',$rangeItem->rightMargin);
        }
      }
    }
  }

} 