<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;

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
  /** @var PreprocessingFactory $preprocessingFactory */
  private $preprocessingFactory;

  /**
   * @param string $appVersion
   * @param DatabaseFactory $databaseFactory
   * @param PreprocessingFactory $preprocessingFactory
   */
  public function __construct($appVersion='', DatabaseFactory $databaseFactory, PreprocessingFactory $preprocessingFactory) {
    $this->appVersion=$appVersion;
    $this->databaseFactory=$databaseFactory;
    $this->preprocessingFactory=$preprocessingFactory;
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|null $pmml
   * @return GuhaPmmlSerializer
   */
  public function createGuhaPmmlSerializer(Task $task, $pmml = null){
    return new GuhaPmmlSerializer($task, $pmml, $this->databaseFactory, $this->preprocessingFactory, $this->appVersion);
  }

  /**
   * @param Task $task
   * @param null $pmml
   * @return PmmlSerializer
   */
  public function createPmmlSerializer(Task $task, $pmml = null){
    return new PmmlSerializer($task, $pmml, $this->databaseFactory, $this->preprocessingFactory, $this->appVersion);
  }

  /**
   * @param Task $task
   * @param null $pmml
   * @return PmmlSerializer
   */
  public function createPmml42Serializer(Task $task, $pmml = null){
    return new Pmml42Serializer($task, $pmml, $this->databaseFactory, $this->preprocessingFactory, $this->appVersion);
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|null $pmml
   * @return CloudDriverGuhaPmmlSerializer
   */
  public function createCloudDriverGuhaPmmlSerializer(Task $task, $pmml=null){
    return new CloudDriverGuhaPmmlSerializer($task, $pmml, $this->databaseFactory, $this->preprocessingFactory, $this->appVersion);
  }

}