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
 * @property string $specialType = ''
 * @property User|null $user m:hasOne
 * @property bool $shared = false
 * @property ValuesBin[] $valuesBins m:hasMany
 * @property Attribute[] $generatedAttributes m:belongsToMany
 */
class Preprocessing extends Entity{

  const SPECIALTYPE_EACHONE='eachOne';
  const NEW_PREPROCESSING_EACHONE_NAME="Each value - one category";

} 