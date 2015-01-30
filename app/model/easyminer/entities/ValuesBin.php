<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class ValuesBin
 * @package App\Model\EasyMiner\Entities
 * @property int $valuesBinId
 * @property string $name
 * @property Format $format m:hasOne
 * @property Interval[] $intervals m:hasMany //XXX
 * @property Value[] $values m:hasMany //XXX
 */
class ValuesBin extends Entity{

} 