<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class Task - entita zachycující jednu konkrétní dataminingovou úlohu
 * @package App\Model\EasyMiner\Entities
 * @property int|null $taskId=null
 * @property string $name = ''
 * @property Miner $miner m:hasOne
 */
class Task extends Entity{

} 