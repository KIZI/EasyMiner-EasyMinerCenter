<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class RuleSetRuleRelation
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
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