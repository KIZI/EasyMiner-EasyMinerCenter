<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class KnowledgeBaseRuleRelation
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Přemysl Václav Duben
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $knowledgeBaseRuleRelationId
 * @property int $ruleSetId
 * @property int $ruleId
 * @property int $knowledgeBaseRuleId
 * @property string $relation m:Enum(self::RELATION_*)
 * @property float $rate
 * @property \DateTime|null $resultDate = null
 */
class KnowledgeBaseRuleRelation extends Entity{
    const RELATION_POSITIVE='positive';
    const RELATION_NEUTRAL='neutral';
    const RELATION_NEGATIVE='negative';

} 