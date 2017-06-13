<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class ValuesBin
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $valuesBinId
 * @property string $name
 * @property Format $format m:hasOne
 * @property Interval[] $intervals m:hasMany
 * @property Value[] $values m:hasMany
 * @method addToValues(Value $value)
 * @method removeFromValues(Value $value)
 * @method addToIntervals(Interval $interval)
 * @method removeFromIntervals(Interval $interval)
 */
class ValuesBin extends Entity{

} 