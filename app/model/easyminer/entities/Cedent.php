<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;

use LeanMapper\Entity;


/**
 * Class Cedent
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $cedentId
 * @property string $connective = 'conjunction' m:Enum(self::CONNECTIVE_*)
 * @property Cedent[] $cedents m:hasMany(parent_cedent_id:cedents_relations:child_cedent_id:cedents)
 * @property RuleAttribute[] $ruleAttributes m:hasMany(cedent_id:cedents_rule_attributes:rule_attribute_id:rule_attributes)
 * @method addToCedents(Cedent $cedent)
 * @method removeFromCedents(Cedent $cedent)
 * @method addToRuleAttributes(RuleAttribute $ruleAttribute)
 * @method removeFromRuleAttributes(RuleAttribute $ruleAttribute)
 */
class Cedent extends Entity{
  const CONNECTIVE_CONJUNCTION='conjunction';
  const CONNECTIVE_DISJUNCTION='disjunction';
  const CONNECTIVE_NEGATION='negation';

  /**
   * @return string[]
   */
  public static function getConnectives(){
    return [self::CONNECTIVE_CONJUNCTION,self::CONNECTIVE_DISJUNCTION,self::CONNECTIVE_NEGATION];
  }
} 