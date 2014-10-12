<?php

namespace app\model\easyminer\entities;

use LeanMapper\Entity;

/**
 * Class HelperData - entita pro ukládání pomocných dat EasyMineru
 * @package app\model\easyminer\entities
 * @property int $helperDataId
 * @property Miner $miner m:hasOne
 * @property string $type
 * @property string $data
 */
class HelperData extends Entity{

} 