<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;

/**
 * Class XmlSerializersFactory - Factory třída pro vytváření XML serializérů
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
class XmlSerializersFactory {
  /** @var string $appVersion */
  private $appVersion;
  /** @var DatabaseFactory $databaseFactory */
  private $databaseFactory;

  /**
   * @param string $appVersion
   * @param DatabaseFactory $databaseFactory
   */
  public function __construct($appVersion='', DatabaseFactory $databaseFactory) {
    $this->appVersion=$appVersion;
    $this->databaseFactory=$databaseFactory;
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|null $pmml
   * @return GuhaPmmlSerializer
   */
  public function createGuhaPmmlSerializer(Task $task, $pmml = null){
    return new GuhaPmmlSerializer($task, $pmml, $this->databaseFactory, $this->appVersion);
  }

}