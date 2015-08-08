<?php

namespace App\Model\EasyMiner\Serializers;

use App\Model\EasyMiner\Entities\Cedent;
use App\Model\EasyMiner\Entities\Interval;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleAttribute;
use App\Model\EasyMiner\Entities\Value;
use App\Model\EasyMiner\Entities\ValuesBin;

/**
 * Class AssociationRulesXmlSerializer - serializer umožňující sestavit AssociationRules XML
 * @package App\Model\EasyMiner\Serializers
 */
class AssociationRulesXmlSerializer {

  const DEFAULT_XMLNS='http://keg.vse.cz/lm/AssociationRules/v1.0';
  /** @var  \SimpleXMLElement $xml */
  private $xml;

  /**
   * @param Rule[] $rules
   */
  public function __construct($rules=null){
    $this->xml=$this->prepareBlankXml();
    if (!empty($rules)){
      $this->serializeRules($rules);
    }
  }

  /**
   * @param Rule[] $rules
   */
  public function serializeRules($rules){
    if (!empty($rules)){
      foreach($rules as $rule){
        $this->serializeRule($rule);
      }
    }
  }

  public function getXml(){
    return $this->xml;
  }

  private function serializeRule(Rule $rule){
    if (empty($rule->antecedent)){
      //FIXME remove!!! jen dočasné opatření...
      return;
    }
    $ruleXml=$this->xml->addChild('AssociationRule');
    $ruleXml->addAttribute('id',$rule->ruleId);
    if (!empty($rule->antecedent)){
      $this->serializeCedent($rule->antecedent,$ruleXml,'Antecedent');
    }
    if (!empty($rule->consequent)){
      $this->serializeCedent($rule->consequent,$ruleXml,'Consequent');
    }
    $ratingXml=$ruleXml->addChild('Rating');
    $ratingXml->addAttribute('confidence',$rule->confidence);
    $ratingXml->addAttribute('support',$rule->support);
    $fourFtTableXml=$ruleXml->addChild('FourFtTable');
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
  private function serializeRuleAttribute(RuleAttribute $ruleAttribute, &$parentElement){
    $attributeXml=$parentElement->addChild('Attribute');
    $attribute=$ruleAttribute->attribute;
    $attributeXml->addChild('Column',$attribute->datasourceColumn->name);//TODO zbytečně složité...
    $categoryXml=$attributeXml->addChild('Category');
    $dataXml=$categoryXml->addChild('Data');
    if (!empty($ruleAttribute->valuesBin)){
      $this->serializeValuesBin($ruleAttribute->valuesBin,$dataXml);
    }
    if (!empty($ruleAttribute->value)){
      $this->serializeValue($ruleAttribute->value,$dataXml);
    }
  }

  private function serializeValuesBin(ValuesBin $valuesBin, &$parentElement){
    if (!empty($valuesBin->values)){
      foreach($valuesBin->values as $value){
        $this->serializeValue($value, $parentElement);
      }
    }
    if (!empty($valuesBin->intervals)){
      foreach($valuesBin->intervals as $interval){
        $this->serializeInterval($interval, $parentElement);
      }
    }
  }

  /**
   * @param Value $value
   * @param \SimpleXMLElement $parentElement
   */
  private function serializeValue(Value $value, &$parentElement){
    $parentElement->addChild('Value',$value->value);
  }

  /**
   * @param Interval $interval
   * @param \SimpleXMLElement $parentElement
   */
  private function serializeInterval(Interval $interval, &$parentElement){
    $intervalXml=$parentElement->addChild('Interval');
    $intervalXml->addAttribute('leftMargin',$interval->leftMargin);
    $intervalXml->addAttribute('rightMargin',$interval->rightMargin);
    $intervalXml->addAttribute('closure',$interval->leftClosure.ucfirst($interval->rightClosure));
  }

  /**
   * Funkce připravující prázdný PMML dokument
   */
  private function prepareBlankXml(){
    return simplexml_load_string('<'.'?xml version="1.0" encoding="UTF-8"?><AssociationRules xmlns="'.self::DEFAULT_XMLNS.'"></AssociationRules>');
  }

}