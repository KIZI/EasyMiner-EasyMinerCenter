<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;


use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;

/**
 * Class BreRuleSerializer
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BreRuleSerializer{

  const DEFAULT_XMLNS='http://easyminer.eu/Rule/1.0';

  /** @var  \SimpleXMLElement $xml */
  private $xml;

  /**
   * BreRuleSerializer constructor.
   */
  public function __construct(){
    $this->xml=self::prepareBlankXml();
  }

  /**
   * @return \SimpleXMLElement
   */
  public function getXml(){
    return $this->xml;
  }

  /**
   * Metoda pro serializaci pravidla
   * @param Rule $rule
   */
  public function serializeRule(Rule $rule){
    $this->xml->addAttribute('id',$rule->ruleId);
    $textXml=$this->xml->addChild('text');
    $textXml[]=$rule->text;
    if (!empty($rule->antecedent)){
      $this->serializeCedent($rule->antecedent,$this->xml,'Antecedent');
    }
    if (!empty($rule->consequent)){
      $this->serializeCedent($rule->consequent,$this->xml,'Consequent');
    }
    $ratingXml=$this->xml->addChild('Rating');
    $ratingXml->addAttribute('confidence',$rule->confidence);
    $ratingXml->addAttribute('support',$rule->support);
    $fourFtTableXml=$this->xml->addChild('FourFtTable');
    $fourFtTableXml->addAttribute('a',$rule->a);
    $fourFtTableXml->addAttribute('b',$rule->b);
    $fourFtTableXml->addAttribute('c',$rule->c);
    $fourFtTableXml->addAttribute('d',$rule->d);
  }

  /**
   * @param Cedent $cedent
   * @param \SimpleXMLElement $parentElement
   * @param string $createElementName=Cedent - název elementu, který se má vytvořit
   */
  private function serializeCedent(Cedent $cedent, &$parentElement, $createElementName="Cedent"){
    $cedentXml=$parentElement->addChild($createElementName);
    $cedentXml->addAttribute('connective',ucfirst($cedent->connective));
    if (!empty($cedent->cedents)){
      foreach($cedent->cedents as $subCedent){
        $this->serializeCedent($subCedent, $cedentXml);
      }
    }
    if (!empty($cedent->ruleAttributes)){
      foreach($cedent->ruleAttributes as $ruleAttribute){
        $this->serializeRuleAttribute($ruleAttribute, $cedentXml);
      }
    }
  }

  /**
   * @param RuleAttribute $ruleAttribute
   * @param \SimpleXMLElement $parentElement
   */
  private function serializeRuleAttribute(RuleAttribute $ruleAttribute,\SimpleXMLElement &$parentElement){
    $attribute=$ruleAttribute->attribute;
    $ruleAttributeXml=$parentElement->addChild('RuleAttribute');
    $ruleAttributeXml->addAttribute('attribute',$attribute->attributeId);
    if (!empty($ruleAttribute->valuesBin)){
      $this->serializeValuesBin($ruleAttribute->valuesBin,$ruleAttributeXml);
    }
    if (!empty($ruleAttribute->value)){
      $this->serializeValue($ruleAttribute->value,$ruleAttributeXml);
    }
  }

  /**
   * @param ValuesBin $valuesBin
   * @param \SimpleXMLElement $parentElement
   */
  private function serializeValuesBin(ValuesBin $valuesBin, \SimpleXMLElement &$parentElement){
    $valuesBinXml=$parentElement->addChild('ValuesBin');
    $valuesBinXml->addAttribute('id',$valuesBin->valuesBinId);
    $valuesBinXml[0]=$valuesBin->name;
  }

  /**
   * @param Value $value
   * @param \SimpleXMLElement $parentElement
   */
  private function serializeValue(Value $value, \SimpleXMLElement &$parentElement){
    $valueXml=$parentElement->addChild('Value');
    $valueXml->addAttribute('id',$value->valueId);
    $valueXml[0]=$value->value;
  }

  /**
   * Statická metoda připravující prázdný PMML dokument
   */
  private static function prepareBlankXml(){
    return simplexml_load_string('<'.'?xml version="1.0" encoding="UTF-8"?><Rule xmlns="'.self::DEFAULT_XMLNS.'"></Rule>');
  }
}