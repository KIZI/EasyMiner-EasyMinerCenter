<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Preprocessing
 *
 * @package App\Model\EasyMiner\Entities
 * @property int $preprocessingId
 * @property Format $format m:hasOne
 * @property string $name
 * @property string $specialType
 * @property ValuesBin[] $valuesBins m:hasMany
 * @property Attribute[] $generatedAttributes m:belongsToMany
 */
class Preprocessing extends Entity{

  const SPECIALTYPE_EACHONE='eachOne';

} 