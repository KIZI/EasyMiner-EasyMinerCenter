<?php

namespace App\Model\EasyMiner\Entities;

use LeanMapper\Entity;
use Nette;

/**
 * Class Miner
 * @package App\Model\EasyMiner\Entities
 *
 * @property int|null $minerId = null
 * @property int|null $userId = null
 * @property string $name = ''
 * @property string $type m:Enum('lm','r')
 * @property DataSource|null $datasource m:belongsToOne
 */
class Miner extends Entity{

}