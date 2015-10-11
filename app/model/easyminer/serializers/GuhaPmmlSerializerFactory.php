<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;


use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;

class GuhaPmmlSerializerFactory {
  /** @var string $appVersion */
  private $appVersion;

  /**
   * @param string $appVersion
   */
  public function __construct($appVersion='') {
    $this->appVersion=$appVersion;
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|null $pmml
   * @param DatabasesFacade|null $databasesFacade
   * @param bool $prepareTaskSettingsPmml=false
   * @return GuhaPmmlSerializer
   */
  public function create(Task $task, $pmml = null, $databasesFacade=null, $prepareTaskSettingsPmml=false){
    $guhaPmmlSerializer=new GuhaPmmlSerializer($task,$pmml,$databasesFacade,$this->appVersion);
    if ($prepareTaskSettingsPmml){
      $guhaPmmlSerializer->appendTaskSettings();
      $guhaPmmlSerializer->appendDataDictionary();
      $guhaPmmlSerializer->appendTransformationDictionary();
    }
    return $guhaPmmlSerializer;
  }

}