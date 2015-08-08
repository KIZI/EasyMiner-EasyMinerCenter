<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class RuleSetRuleRelation
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $ruleSetRuleRelationId
 * @property Rule $rule m:hasOne
 * @property RuleSet $ruleSet m:hasOne
 * @property string $relation m:Enum(self::RELATION_*)
 */
class RuleSetRuleRelation extends Entity{
  const RELATION_POSITIVE='positive';
  const RELATION_NEUTRAL='neutral';
  const RELATION_NEGATIVE='negative';

} 