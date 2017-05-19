<?php
namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use EasyMinerCenter\Model\EasyMiner\Entities\KnowledgeBase;
use EasyMinerCenter\Model\EasyMiner\Entities\MetaAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;

/**
 * Class XmlSerializer - Serializer pro export pravidel v jednoduchém formátu
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class XmlSerializer {
  #region base XML templates
  const METAATTRIBUTES_XML_BASE = '<MetaAttributes xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttributes>';
  const METAATTRIBUTE_XML_BASE  = '<MetaAttribute xmlns="http://keg.vse.cz/easyminer/BKEF"></MetaAttribute>';
  const ATTRIBUTES_XML_BASE     = '<Attributes xmlns="http://keg.vse.cz/easyminer/BKEF"></Attributes>';
  const ATTRIBUTE_XML_BASE      = '<Attribute xmlns="http://keg.vse.cz/easyminer/BKEF"></Attribute>';
  const RULE_XML_BASE           = '<Rule xmlns="http://keg.vse.cz/easyminer/KBRules"></Rule>';
  const RULES_XML_BASE          = '<Rules xmlns="http://keg.vse.cz/easyminer/KBRules"></Rules>';
  const RULESET_XML_BASE        = '<RuleSet xmlns="http://keg.vse.cz/easyminer/KBRules"></RuleSet>';
  const RULESETS_XML_BASE       = '<RuleSets xmlns="http://keg.vse.cz/easyminer/KBRules"></RuleSets>';
  const KNOWLEDGEBASE_XML_BASE  = '<KnowledgeBase xmlns="http://keg.vse.cz/easyminer/KB"></KnowledgeBase>';
  const KNOWLEDGEBASES_XML_BASE = '<KnowledgeBases xmlns="http://keg.vse.cz/easyminer/KB"></KnowledgeBases>';

  /**
   * Static method returning basic structure of XML file for list of metaattributes
   * @return \SimpleXMLElement
   */
  public static function baseMetaAttributesXml(){
    return simplexml_load_string(self::METAATTRIBUTES_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for one metaattribute
   * @return \SimpleXMLElement
   */
  public static function baseMetaAttributeXml(){
    return simplexml_load_string(self::METAATTRIBUTE_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for list of attributes
   * @return \SimpleXMLElement
   */
  public static function baseAttributesXml(){
    return simplexml_load_string(self::ATTRIBUTES_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for one attribute
   * @return \SimpleXMLElement
   */
  public static function baseAttributeXml(){
    return simplexml_load_string(self::ATTRIBUTE_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for list of rules
   * @return \SimpleXMLElement
   */
  public static function baseRulesXml(){
    return simplexml_load_string(self::RULES_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for one rule
   * @return \SimpleXMLElement
   */
  public static function baseRuleXml(){
    return simplexml_load_string(self::RULE_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for ruleset
   * @return \SimpleXMLElement
   */
  public static function baseRuleSetXml() {
    return simplexml_load_string(self::RULESET_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for list of rulesets
   * @return \SimpleXMLElement
   */
  public static function baseRuleSetsXml() {
    return simplexml_load_string(self::RULESETS_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for one knowledgeBase
   * @return \SimpleXMLElement
   */
  public static function baseKnowledgeBaseXml() {
    return simplexml_load_string(self::KNOWLEDGEBASE_XML_BASE);
  }

  /**
   * Static method returning basic structure of XML file for list of knowledgeBases
   * @return \SimpleXMLElement
   */
  public static function baseKnowledgeBasesXml() {
    return simplexml_load_string(self::KNOWLEDGEBASES_XML_BASE);
  }

  #endregion base XML templates

  /**
   * Method for serialization of complete metaattribute with the full structure
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
   * Method for serialization of one metaattribute only with list of formats (with URIs and names, without the inner structure)
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
   * Method for serialization of basic info about metaattribute without inner structure
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
    $metaAttributeXml->addAttribute('id',$metaAttribute->metaAttributeId);
    $nameNode=$metaAttributeXml->addChild('Name');
    $nameNode[0]=$metaAttribute->name;
    return $metaAttributeXml;
  }


  /**
   * Method for serialization of basic info about attribute (without inner structure)
   * @param Attribute $attribute
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankAttributeAsXml(Attribute $attribute,\SimpleXMLElement &$parentXml=null){
    if ($parentXml instanceof \SimpleXMLElement){
      $attributeXml=$parentXml->addChild('Attribute');
    }else{
      $attributeXml=self::baseAttributeXml();
    }
    $attributeXml->addAttribute('id',$attribute->attributeId);
    $attributeXml->addAttribute('format',@$attribute->preprocessing->format->formatId);
    if (!empty($attribute->preprocessing)){
      $attributeXml->addAttribute('preprocessing',@$attribute->preprocessing->preprocessingId);
    }
    $nameNode=$attributeXml->addChild('Name');
    $nameNode[0]=$attribute->name;
    return $attributeXml;
  }

  /**
   * Method for serialization of basic info about Format (without inner structure)
   * @param Format $format
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankFormatAsXml(Format $format,\SimpleXMLElement &$parentXml = null){
    $formatXml=$parentXml->addChild('Format');
    $formatXml->addAttribute('id',$format->formatId);
    $nameNode=$formatXml->addChild('Name');
    $nameNode[0]=$format->name;
    return $formatXml;
  }

  /**
   * Method for serialization of basic info about RuleSet (without inner structure)
   * @param  RuleSet $ruleSet
   * @param  \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankRuleSetAsXml(RuleSet $ruleSet,\SimpleXMLElement &$parentXml = null){
    if ($parentXml instanceof \SimpleXMLElement){
      $ruleSetXml=$parentXml->addChild('RuleSet');
    }else{
      $ruleSetXml=self::baseRuleSetXml();
    }
    $ruleSetXml->addAttribute('id',$ruleSet->ruleSetId);
    $nameNode=$ruleSetXml->addChild('Name');
    $nameNode[0]=$ruleSet->name;
    return $ruleSetXml;
  }

  /**
   * Method for serialization of basic info about KnowledgeBase (without inner structure)
   * @param  KnowledgeBase $knowledgeBase
   * @param  \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function blankKnowledgeBaseAsXml(KnowledgeBase $knowledgeBase,\SimpleXMLElement &$parentXml = null){
    if ($parentXml instanceof \SimpleXMLElement){
      $knowledgeBaseXml=$parentXml->addChild('KnowledgeBase');
    }else{
      $knowledgeBaseXml=self::baseKnowledgeBaseXml();
    }
    $knowledgeBaseXml->addAttribute('id',$knowledgeBase->knowledgeBaseId);
    $nameNode=$knowledgeBaseXml->addChild('Name');
    $nameNode[0]=$knowledgeBase->name;
    return $knowledgeBaseXml;
  }

  /**
   * Method for serialization of Format packed in basic structure of metaattribute
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
   * Method for serialization of a format (with complete structure)
   * @param Format $format
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function formatAsXml(Format $format,\SimpleXMLElement &$parentXml = null){
    $formatXml=$this->blankFormatAsXml($format,$parentXml);
    $formatXml->addChild('DataType',$format->dataType);
    $rangeXml=$formatXml->addChild('Range');
    $intervals=$format->intervals;
    $this->rangeAsXml($intervals,$rangeXml);
    $this->rangeAsXml($format->values,$rangeXml);

    $valuesBins=$format->valuesBins;
    $valuesBinsXml=$formatXml->addChild('ValuesBins');
    if (count($valuesBins)){
      foreach ($valuesBins as $valuesBin){
        $valuesBinXml=$valuesBinsXml->addChild('Bin');
        $valuesBinXml->addAttribute('id',$valuesBin->valuesBinId);
        $nameNode=$valuesBinXml->addChild('Name');
        $nameNode[0]=$valuesBin->name;
        $this->rangeAsXml($valuesBin->intervals,$valuesBinXml);
        $this->rangeAsXml($valuesBin->values,$valuesBinXml);
      }
    }
    return $formatXml;
  }

  /**
   * Method for serialization of a range (enumeration of values or intervals)
   * @param \SimpleXMLElement $parentXml
   * @param Interval[]|Value[] $range
   */
  private function rangeAsXml($range,\SimpleXMLElement &$parentXml){
    if (count($range) > 0){
      foreach ($range as $rangeItem){
        if ($rangeItem instanceof Value){
          //serialize concrete values
          $valueXml=$parentXml->addChild('Value');
          $valueXml[0]=$rangeItem->value;
          if ($rangeItem->valueId){
            $valueXml->addAttribute('id',$rangeItem->valueId);
          }
        }elseif($rangeItem instanceof Interval){
          //serialize intervals
          $intervalXml=$parentXml->addChild('Interval');
          $intervalXml->addAttribute('id',$rangeItem->intervalId);
          $closure=@$rangeItem->closure;//XXX
          $intervalXml->addAttribute('closure',(string)$closure);
          $leftMargin=@$rangeItem->leftMargin;
          $intervalXml->addAttribute('leftMargin',(string)$leftMargin);
          $rightMargin=@$rangeItem->rightMargin;
          $intervalXml->addAttribute('rightMargin',(string)$rightMargin);
        }
      }
    }
  }

  /**
   * Method for serialization of a rule from knowledge base (only ID and text)
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
    $ruleXml->addAttribute('id',$rule->ruleId);
    if (!empty($rule->text)){
      $ruleTextXml=$ruleXml->addChild('Text');
      $ruleXml->Text[0]=$rule->text;
    }
    return $ruleXml;
  }

  /**
   * Method for serialization fo a rule from knowledge base (with complete inner structure)
   * @param Rule $rule
   * @param \SimpleXMLElement $parentXml
   * @return \SimpleXMLElement
   */
  public function ruleAsXml(Rule $rule,\SimpleXMLElement &$parentXml = null){
    $ruleXml=$this->blankRuleAsXml($rule,$parentXml);
    if (!empty($rule->antecedent)){
      $this->cedentAsXml($rule->antecedent,$ruleXml,'Antecedent');
    }
    if (!empty($rule->consequent)) {
      $this->cedentAsXml($rule->consequent, $ruleXml, 'Consequent');
    }
    return $ruleXml;
  }

  /**
   * Private method for serialization of a cedent
   * @param Cedent $cedent
   * @param \SimpleXMLElement $parentXml
   * @param string $elementName = 'Cedent'
   * @return \SimpleXMLElement
   */
  private function cedentAsXml(Cedent $cedent,\SimpleXMLElement &$parentXml,$elementName='Cedent'){
    $cedentXml=$parentXml->addChild($elementName);
    $cedentXml->addAttribute('id',$cedent->cedentId);
    $cedentXml->addAttribute('connective',$cedent->connective);

    if (count($cedent->ruleAttributes)){
      foreach ($cedent->ruleAttributes as $ruleAttribute){
        $this->ruleAttributeAsXml($ruleAttribute,$cedentXml);
      }
    }
    if (count($cedent->cedents)){
      foreach ($cedent->cedents as $subCedent){
        $this->cedentAsXml($subCedent,$cedentXml);
      }
    }
    return $cedentXml;
  }

  /**
   * Private method for serialization of a ruleattribute (attribute from rule)
   * @param RuleAttribute $ruleAttribute
   * @param \SimpleXMLElement &$parentXml
   * @return \SimpleXMLElement
   */
  private function ruleAttributeAsXml(RuleAttribute $ruleAttribute,\SimpleXMLElement &$parentXml){
    $ruleAttributeXml=$parentXml->addChild('RuleAttribute');
    $ruleAttributeXml->addAttribute('id',$ruleAttribute->ruleAttributeId);
    $attribute=$ruleAttribute->attribute;
    $ruleAttributeXml->addAttribute('attribute',$attribute->attributeId);
    $ruleAttributeXml->addAttribute('preprocessing',$attribute->preprocessing->preprocessingId);
    $ruleAttributeXml->addAttribute('format',$attribute->preprocessing->format->formatId);
    if (!empty($ruleAttribute->valuesBin)){
      $valuesBinXml=$ruleAttributeXml->addChild('ValuesBin');
      $valuesBinXml->addAttribute('id',$ruleAttribute->valuesBin->valuesBinId);
    }
    if (!empty($ruleAttribute->value)){
      $valuesBinXml=$ruleAttributeXml->addChild('Value');
      $valuesBinXml->addAttribute('id',$ruleAttribute->value->valueId);
    }
    return $ruleAttributeXml;
  }

  /**
   * @param RuleSet $ruleSet
   * @param \SimpleXMLElement &$parentXml=null
   * @return \SimpleXMLElement
   */
  public function ruleSetAsXml(RuleSet $ruleSet,\SimpleXMLElement &$parentXml=null) {
    $ruleSetXml = $this->blankRuleSetAsXml($ruleSet,$parentXml);
    $ruleSetRuleRelations=$ruleSet->ruleSetRuleRelations;
    if (count($ruleSetRuleRelations)){
      foreach ($ruleSetRuleRelations as $ruleSetRuleRelation){
        $this->blankRuleAsXml($ruleSetRuleRelation->rule,$ruleSetXml);
      }
    }
    return $ruleSetXml;
  }

  /**
   * @param KnowledgeBase $knowledgeBase
   * @param \SimpleXMLElement &$parentXml=null
   * @return \SimpleXMLElement
   */
  public function knowledgeBaseAsXml(KnowledgeBase $knowledgeBase,\SimpleXMLElement &$parentXml=null) {
    return $this->blankKnowledgeBaseAsXml($knowledgeBase,$parentXml);
  }



  /**
   * Method for serialization of complete attribute with complete inner structure
   * @param Attribute $attribute
   * @param \SimpleXMLElement|null &$parentXml
   * @return \SimpleXMLElement
   */
  public function attributeAsXml(Attribute $attribute,&$parentXml=null){
    $attributeXml=$this->blankAttributeAsXml($attribute,$parentXml);

    if ($format=$attribute->preprocessing->format){
      $rangeXml=$attributeXml->addChild('Range');
      //for simplification, serialize also Range from Format
      $this->rangeAsXml($format->intervals,$rangeXml);
      $this->rangeAsXml($format->values,$rangeXml);
    }

    $valuesBins=$attribute->valuesBins;//XXX
    $valuesBinsXml=$attributeXml->addChild('ValuesBins');
    if (count($valuesBins)){
      foreach ($valuesBins as $valuesBin){
        $valuesBinXml=$valuesBinsXml->addChild('Bin');
        $valuesBinXml->addAttribute('id',$valuesBin->valuesBinId);
        $nameNode=$valuesBinXml->addChild('Name');
        $nameNode[0]=$valuesBin->name;
        $this->rangeAsXml($valuesBin->intervals,$valuesBinXml);
        $this->rangeAsXml($valuesBin->values,$valuesBinXml);
      }
    }
    return $attributeXml;
  }
}