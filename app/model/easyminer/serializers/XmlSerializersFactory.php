<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;


use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;

/**
 * Class XmlSerializersFactory - Factory třída pro vytváření XML serializérů
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 */
class XmlSerializersFactory {
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
  public function createGuhaPmmlSerializer(Task $task, $pmml = null, $databasesFacade=null){
    //TODO výhledově přesun DatabasesFacade do této třídy...
    $guhaPmmlSerializer=new GuhaPmmlSerializer($task,$pmml,$databasesFacade,$this->appVersion);
    return $guhaPmmlSerializer;
  }

}