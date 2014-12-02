<?php
namespace App\Model\EasyMiner\Entities;

use LeanMapper\Entity;


/**
 * Class Cedent
 * @package app\model\easyminer\entities
 * @property int $cedentId
 * @property string $connective = 'conjunction' m:Enum(self::CONNECTIVE_*)
 * @property Cedent[] $cedents m:hasMany(parent_cedent_id:cedents_relations:child_cedent_id:cedents)
 * @property RuleAttribute[] $ruleAttributes m:hasMany(cedent_id:cedents_rule_attributes:rule_attribute_id:rule_attributes)
 */
class Cedent extends Entity{
  const CONNECTIVE_CONJUNCTION='conjunction';
  const CONNECTIVE_DISJUNCTION='disjunction';
  const CONNECTIVE_NEGATION='negation';

} 