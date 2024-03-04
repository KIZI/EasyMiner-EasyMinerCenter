<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use Nette\Utils\Json;

/**
 * Class Task - entita zachycující jednu konkrétní dataminingovou úlohu
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $taskId=null
 * @property string $type m:Enum(Miner::TYPE_*)
 * @property int $rulesInRuleClipboardCount = 0
 * @property int $rulesCount = 0
 * @property string $rulesOrder = 'default'
 * @property string $name = ''
 * @property Miner $miner m:hasOne
 * @property string $state m:Enum(self::STATE_*)
 * @property string $taskSettingsJson = ''
 * @property string|null $resultsUrl = ''
 * @property string $importState = 'none' m:Enum(self::IMPORT_STATE_*)
 * @property string $importJson = ''
 * @property-read TaskState $taskState
 * @property-read Rule[] $rules m:belongsToMany
 * @property \DateTime|null $created = null
 * @property \DateTime $lastModified
 */
class Task extends Entity{
  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_SOLVED='solved';
  const STATE_FAILED='failed';
  const STATE_INTERRUPTED='interrupted';

  const IMPORT_STATE_NONE='none';
  const IMPORT_STATE_WAITING='waiting';
  const IMPORT_STATE_PARTIAL='partial';
  const IMPORT_STATE_DONE='done';


  /**
   * Method returning an array with basic data properties
   * @param bool $includeSettings = false - it $includeSettings is true, the returned array includes also complete task settings
   * @return array
   */
  public function getDataArr($includeSettings=false){
    $result=[
      'id'=>$this->taskId,
      'miner'=>$this->miner->minerId,
      'name'=>$this->name,
      'type'=>$this->type,
      'state'=>$this->state,
      'importState'=>$this->importState,
      'rulesCount'=>$this->rulesCount,
      'rulesOrder'=>$this->rulesOrder
    ];
    if ($includeSettings){
      $result['settings']=$this->getTaskSettings();
    }
    return $result;
  }

  /**
   * Method returning an array with settings of this task
   * @return array
   * @throws \Nette\Utils\JsonException
   */
  public function getTaskSettings() {
    return Json::decode($this->taskSettingsJson,Json::FORCE_ARRAY);
  }

  /**
   * Method for setting of this task
   * @param array|string|object $settings
   * @throws \Nette\Utils\JsonException
   */
  public function setTaskSettings($settings) {
    if (!empty($settings) && (is_array($settings) || is_object($settings))){
      $settings=Json::encode($settings);
    }
    $this->taskSettingsJson=$settings;
  }

  /**
   * Method returning data of import configuration/state
   * @return array
   * @throws \Nette\Utils\JsonException
   */
  public function getImportData() {
    if (empty($this->importJson)){
      return [];
    }
    return Json::decode($this->importJson,Json::FORCE_ARRAY);
  }

  /**
   * Method for setting of data of import configuration/state
   * @param array $importData
   * @throws \Nette\Utils\JsonException
   */
  public function setImportData($importData) {
    if (!empty($importData) && is_array($importData) || is_object($importData)){
      $importData=Json::encode($importData);
    }elseif($importData===[]){
      $importData="";
    }
    $this->importJson=$importData;
  }

  /**
   * @return TaskState
   */
  public function getTaskState(){
    return new TaskState($this,$this->state,$this->rulesCount,$this->resultsUrl,$this->importState,$this->getImportData());
  }

  /**
   * Method returning the info about task state on the mining service - true, if the task was finished or stopped/interrupted
   * @return bool
   */
  public function isMiningFinished() {
    return $this->state!=self::STATE_NEW && $this->state!=self::STATE_IN_PROGRESS;
  }

  /**
   * Method returning the info about task state - true, if the task was finished or stopped/interrupted on the mining service and if the import of results was also finished
   * @return bool
   */
  public function isMiningAndImportFinished(){
    if (!$this->isMiningFinished()){return false;}
    return ($this->importState!=self::IMPORT_STATE_PARTIAL && $this->importState!=self::IMPORT_STATE_WAITING);
  }

  /**
   * Method returning a list of interest measures used in this Task
   * @return string[]
   */
  public function getInterestMeasures($includeSpecialIMs=false){
    $result=array();
    try{
      $taskSettings=Json::decode($this->taskSettingsJson,Json::FORCE_ARRAY);
    }catch (\Exception $e){}
    if (!empty($taskSettings['rule0']['IMs'])){
      foreach ($taskSettings['rule0']['IMs'] as $IM){
        $result[$IM['name']]=['name'=>$IM['name'],'threshold'=>isset($IM['threshold'])?$IM['threshold']:null];
      }
    }
    if (!empty($taskSettings['rule0']['specialIMs'])){
      foreach ($taskSettings['rule0']['specialIMs'] as $IM){
        $result[$IM['name']]=['name'=>$IM['name']];
      }
    }
    return $result;
  }

  /**
   * @param string $rulesOrder
   */
  public function setRulesOrder($rulesOrder){
    $rulesOrder=strtolower($rulesOrder);
    $IMsArr=array_keys($this->getInterestMeasures());
    $supportedIM=false;
    foreach($IMsArr as $im){
      if (strtolower($im)==$rulesOrder){
        $supportedIM=true;
        break;
      }
    }
    if ($rulesOrder=='default'){
      $supportedIM=true;
    }
    if ($supportedIM){
      /** @noinspection PhpUndefinedFieldInspection */
      $this->row->rules_order=$rulesOrder;
    }else{
      throw new \InvalidArgumentException('Unsupported interest measure!');
    }
  }

  /**
   * @return string
   */
  public function getRulesOrder() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->row->rules_order;
  }
} 