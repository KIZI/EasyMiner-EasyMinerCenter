<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use LeanMapper\Entity;

/**
 * Class RuleAttribute
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $ruleAttributeId
 * @property Attribute $attribute m:hasOne
 * @property ValuesBin|null $valuesBin m:hasOne
 * @property Value|null $value m:hasOne
 */
class RuleAttribute extends Entity{

} 