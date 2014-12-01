<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 1. 12. 2014
 * Time: 11:46
 */

namespace App\Model\EasyMiner\Entities;


use LeanMapper\Entity;

/**
 * Class Rule
 * @package App\Model\EasyMiner\Entities
 * @property int $ruleId
 * @property Task $task m:hasOne
 * @property string $text
 * @property
 */
class Rule extends Entity{

} 