<?php
namespace app\model\easyminer\entities;

use LeanMapper\Entity;


/**
 * Class Cedent
 * @package app\model\easyminer\entities
 * @property int $cedentId
 * @property string $connective = 'conjunction' m:Enum(self::CONNECTIVE_*)
 * @property Cedent $parentCedent m:hasOne(:parent_cedent_id)
 * @property Cedent[] $cedents m:belongsToMany(parent_cedent_id:)
 * @property RuleAttribute[] $ruleAttributes m:belongsToMany
 */
class Cedent extends Entity{
  const CONNECTIVE_CONJUNCTION='conjunction';
  const CONNECTIVE_DISJUNCTION='disjunction';
  const CONNECTIVE_NEGATION='negation';

} 