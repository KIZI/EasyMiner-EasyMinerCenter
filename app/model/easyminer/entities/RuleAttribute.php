<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use LeanMapper\Entity;

/**
 * Class RuleAttribute
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $ruleAttributeId
 * @property Attribute $attribute m:hasOne
 * @property ValuesBin|null $valuesBin m:hasOne
 * @property Value|null $value m:hasOne
 */
class RuleAttribute extends Entity{

} 