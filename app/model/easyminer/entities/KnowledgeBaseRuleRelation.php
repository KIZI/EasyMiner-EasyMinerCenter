<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class KnowledgeBaseRuleRelation
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $knowledgeBaseRuleRelationId
 * @property int $ruleSetId
 * @property int $ruleId
 * @property int $knowledgeBaseRuleId
 * @property string $relation m:Enum(self::RELATION_*)
 * @property float $rate
 */
class KnowledgeBaseRuleRelation extends Entity{
    const RELATION_POSITIVE='positive';
    const RELATION_NEUTRAL='neutral';
    const RELATION_NEGATIVE='negative';

} 