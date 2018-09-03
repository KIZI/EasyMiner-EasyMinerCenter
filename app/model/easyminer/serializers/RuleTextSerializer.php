<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;

/**
 * Class RuleTextSerializer - serializes the text representation of a Rule
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RuleTextSerializer{

  const RULE_PARTS_SEPARATOR='→';
  const CONNECTIVE_CONJUNCTION='&';
  const CONNECTIVE_DISJUNCTION='or';
  const CONNECTIVE_NEGATION='not';
  const EMPTY_ANTECEDENT='*';

  /**
   * Static method for serialization of a Rule to its text form
   * @param Rule $rule
   * @return string
   */
  public static function serialize(Rule $rule){
    $result='';
    if (empty($rule->antecedent)){
      $result.=self::EMPTY_ANTECEDENT;
    }else{
      $result.=self::serializeCedent($rule->antecedent,true);
    }
    $result.=' '.self::RULE_PARTS_SEPARATOR.' ';
    $result.=self::serializeCedent($rule->consequent,true);
    return $result;
  }

  /**
   * @param Cedent $cedent
   * @param bool $isTop=false
   * @return string
   */
  private static function serializeCedent(Cedent $cedent,$isTop=false){
    #region určení spojky
    switch ($cedent->connective){
      case Cedent::CONNECTIVE_CONJUNCTION:
        $connective=self::CONNECTIVE_CONJUNCTION;
        break;
      case Cedent::CONNECTIVE_DISJUNCTION:
        $connective=self::CONNECTIVE_DISJUNCTION;
        break;
      case Cedent::CONNECTIVE_NEGATION:
        $connective=self::CONNECTIVE_NEGATION;
        break;
      default:
        $connective=self::CONNECTIVE_CONJUNCTION;
    }
    #endregion určení spojky

    $resultsArr=[];
    if (!empty($cedent->cedents)){
      foreach ($cedent->cedents as $childCedent){
        $resultsArr[]=self::serializeCedent($childCedent);
      }
    }
    if (!empty($cedent->ruleAttributes)){
      foreach ($cedent->ruleAttributes as $ruleAttribute){
        $resultsArr[]=self::serializeRuleAttribute($ruleAttribute);
      }
    }

    if ($connective==self::CONNECTIVE_NEGATION){
      $result=' not('.join(' '.self::CONNECTIVE_CONJUNCTION.' ',$resultsArr).')';
    }elseif(count($resultsArr)>1){
      $result=join(' '.$connective.' ',$resultsArr);
      if(!$isTop){
        $result='('.$result.')';
      }
    }else{
      $result=array_pop($resultsArr);
    }
    return $result;
  }

  /**
   * @param RuleAttribute $ruleAttribute
   * @return string
   */
  private static function serializeRuleAttribute(RuleAttribute $ruleAttribute){
    $result=$ruleAttribute->attribute->name.'(';
    if (!empty($ruleAttribute->valuesBin)){
      $result.=$ruleAttribute->valuesBin->name;
    }elseif(!empty($ruleAttribute->value)){
      $result.=$ruleAttribute->value->value;
    }
    $result.=')';
    return $result;
  }

}