<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class Task - entita zachycující jednu konkrétní dataminingovou úlohu
 * @package App\Model\EasyMiner\Entities
 * @property int|null $taskId=null
 * @property string $taskUuid = ''
 * @property string $type m:Enum(Miner::TYPE_*)
 * @property bool $inRuleClipboard = false
 * @property string $name = ''
 * @property Miner $miner m:hasOne
 * @property string $state m:Enum(self::STATE_*)
 * @property string $taskSettingsJson = ''
 * @property int $rulesCount = 0
 * @property-read TaskState $taskState
 */
class Task extends Entity{
  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_SOLVED='solved';
  const STATE_FAILED='failed';
  const STATE_INTERRUPTED='interrupted';

  /**
   * @return TaskState
   */
  public function getTaskState(){
    return new TaskState($this->state,$this->rulesCount);
  }
} 