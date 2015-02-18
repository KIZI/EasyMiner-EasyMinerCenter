<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use Nette\Utils\Json;


/**
 * Class Task - entita zachycující jednu konkrétní dataminingovou úlohu
 * @package App\Model\EasyMiner\Entities
 * @property int|null $taskId=null
 * @property string $taskUuid = ''
 * @property string $type m:Enum(Miner::TYPE_*)
 * @property int $rulesInRuleClipboardCount = 0
 * @property int $rulesCount = 0
 * @property string $name = ''
 * @property Miner $miner m:hasOne
 * @property string $state m:Enum(self::STATE_*)
 * @property string $taskSettingsJson = ''
 * @property string|null $resultsUrl = ''
 * @property-read TaskState $taskState
 * @property-read Rule[] $rules m:belongsToMany
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
    return new TaskState($this->state,$this->rulesCount,$this->resultsUrl);
  }

  /**
   * Funkce vracející seznam měr zajímavosti, které jsou použity u dané úlohy
   * @return string[]
   */
  public function getInterestMeasures(){
    $result=array();
    try{
      $taskSettings=Json::decode($this->taskSettingsJson,Json::FORCE_ARRAY);
      $IMs=$taskSettings['rule0']['IMs'];
    }catch (\Exception $e){}
    if (!empty($IMs)){
      foreach ($IMs as $IM){
        $result[]=$IM['name'];
      }
    }
    return $result;
  }
} 