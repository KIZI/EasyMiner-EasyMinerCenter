<?php

namespace App\Model\Rdf\Serializers;

use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\Cedent;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
use App\Model\Rdf\Entities\Value;
use Nette\Object;

/**
 * Class XmlSerializer - třída pro serializaci entit do podoby XML
 * @package App\Model
 */
class XmlSerializer extends Object{
#region base Xml templates
  const METAATTRIBUTES_XML_BASE='<MetaAttributes xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttributes>';
  const METAATTRIBUTE_XML_BASE='<MetaAttribute xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttribute>';
  const RULE_XML_BASE='<Rule xmlns="http://keg.vse.cz/easyminer/KBRules"></Rule>';
  const RULES_XML_BASE='<Rules xmlns="http://keg.vse.cz/easyminer/KBRules"></Rules>';

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

  /**
   * Funkce vracející základ XML dokumentu pro zachycení pravidel
   * @return \SimpleXMLElement
   */
  public static function baseRulesXml(){
    return simplexml_load_string(self::RULES_XML_BASE);
  }

  /**
   * Funkce vracející základ XML dokumentu pro zachycení jednoho pravidla
   * @return \SimpleXMLElement
   */
  public static function baseRuleXml(){
    return simplexml_load_string(self::RULE_XML_BASE);
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
  public function metaAttributeWithBlankFormatsAsXml(MetaAttribute $metaAttribute,\SimpleXMLElement &$parentXml=null){
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
  public function blankMetaAttributeAsXml(MetaAttribute $metaAttribute,\SimpleXMLElement &$parentXml=null){
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
  public function blankFormatAsXml(Format $format,\SimpleXMLElement &$parentXml = null){
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
  public function formatInBlankMetaAttributeAsXml(Format $format,\SimpleXMLElement &$parentXml=null){
    $metaAttributeXml=$this->blankMetaAttributeAsXml($format->metaAttribute,$parentXml);
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
  public function formatAsXml(Format $format,\SimpleXMLElement &$parentXml = null){
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
        $this->rangeAsXml($valuesBin->intervals,$valuesBinXml);
        $this->rangeAsXml($valuesBin->values,$valuesBinXml);
      }
    }
    return $formatXml;
  }

  /**
   * Funkce pro serializaci rozsahu hodnot (výčet hodnot či intervalů)
   * @param \SimpleXMLElement $parentXml
   * @param Interval[]|Value[] $range
   */
  private function rangeAsXml($range,\SimpleXMLElement &$parentXml){
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

  /**
   * Funkce pro serializaci pravidla ze znalostní báze (jen ID a text)
   * @param Rule $rule
   * @param \SimpleXMLElement $parentXml
   * @return \SimpleXMLElement
   */
  public function blankRuleAsXml(Rule $rule,\SimpleXMLElement &$parentXml = null){
    if ($parentXml instanceof \SimpleXMLElement){
      $ruleXml=$parentXml->addChild('Rule');
    }else{
      $ruleXml=self::baseRuleXml();
    }
    $ruleXml->addAttribute('id',$rule->uri);
    if (!empty($rule->text)){
      $ruleXml->addChild('Text',$rule->text);
    }
    return $ruleXml;
  }

  /**
   * Funkce pro serializaci pravidla ze znalostní báze (včetně kompletní struktury)
   * @param Rule $rule
   * @param \SimpleXMLElement $parentXml
   * @return \SimpleXMLElement
   */
  public function ruleAsXml(Rule $rule,\SimpleXMLElement &$parentXml = null){
    $ruleXml=$this->blankRuleAsXml($rule,$parentXml);
    $this->cedentAsXml($rule->antecedent,$ruleXml,'Antecedent');
    $this->cedentAsXml($rule->consequent,$ruleXml,'Consequent');
    if (!empty($rule->rating)){
      $rating=$ruleXml->addChild('Rating');
      foreach ($rule->rating as $key=>$value){
        $rating->addAttribute($key,$value);
      }
    }
  }

  /**
   * Funkce pro serializaci cedentu z pravidla
   * @param Cedent $cedent
   * @param \SimpleXMLElement $parentXml
   * @param string $elementName = 'Cedent'
   * @return \SimpleXMLElement
   */
  private function cedentAsXml(Cedent $cedent,\SimpleXMLElement &$parentXml,$elementName='Cedent'){
    $cedentXml=$parentXml->addChild($elementName);
    $cedentXml->addAttribute('id',$cedent->uri);
    $cedentXml->addAttribute('connective',$cedent->connective);
    if (!empty($cedent->attributes)){
      foreach ($cedent->attributes as $attribute){
        $this->attributeAsXml($attribute,$cedentXml);
      }
    }
    if (!empty($cedent->cedents)){
      foreach ($cedent->cedents as $subCedent){
        $this->cedentAsXml($subCedent,$cedentXml);
      }
    }
    return $cedentXml;
  }

  /**
   * Funkce pro serializaci atributu z konkrétního pravidla
   * @param Attribute $attribute
   * @param \SimpleXMLElement &$parentXml
   * @return \SimpleXMLElement
   */
  private function attributeAsXml(Attribute $attribute,\SimpleXMLElement &$parentXml){
    $attributeXml=$parentXml->addChild('Attribute');
    $attributeXml->addAttribute('id',$attribute->uri);
    $attributeXml->addAttribute('format',$attribute->format->uri);
    if (!empty($attribute->valuesBins)){
      foreach ($attribute->valuesBins as $valuesBin){
        $valuesBinXml=$attributeXml->addChild('ValuesBin');
        $valuesBinXml->addAttribute('id',$valuesBin->uri);
      }
    }
    return $attributeXml;
  }

} 