<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class KnowledgeBaseRuleRelation
 *
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $knowledgeBaseRuleRelationId
 * @property Rule $rule m:hasOne
 * @property Rule $KBrule m:hasOne
 * @property float $rate
 */
class KnowledgeBaseRuleRelation extends Entity{

} 