<?php

namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use Nette\ArgumentOutOfRangeException;
use Nette\NotImplementedException;

/**
 * Class ScorerDriverFactory - class with factory methods for scorer drivers
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ScorerDriverFactory {
  /** @var  array $params */
  private $params;
  /** @var  DatabaseFactory $databaseFactory*/
  private $databaseFactory;
  /** @var XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;

  /**
   * @param array $params
   * @param DatabaseFactory $databaseFactory
   * @param XmlSerializersFactory $xmlSerializersFactory
   */
  public function __construct($params, DatabaseFactory $databaseFactory, XmlSerializersFactory $xmlSerializersFactory) {
    $this->params=$params;
    $this->databaseFactory=$databaseFactory;
    $this->xmlSerializersFactory=$xmlSerializersFactory;
  }

  /**
   * Factory method returning instance of a selected ScorerDriver
   * @param string $scorerType
   * @return IScorerDriver
   */
  public function getScorerInstance($scorerType) {
    /** @var array $driverConfigParams */
    $driverConfigParams=@$this->params['driver_'.$scorerType];
    if (empty($driverConfigParams) || !isset($driverConfigParams['class']) || empty($driverConfigParams['server'])){
      throw new ArgumentOutOfRangeException('Requested scorer driver was not found!');
    }
    $driverClass='\\'.$this->params['driver_'.$scorerType]['class'];
    /** @var IScorerDriver $result */
    $result=new $driverClass($driverConfigParams['server'], $this->databaseFactory, $this->xmlSerializersFactory,$driverConfigParams);
    return $result;
  }

  /**
   * Method returning the default driver (respectively the first one, which is configured)
   * @return IScorerDriver
   * @throws NotImplementedException
   */
  public function getDefaultScorerInstance(){//TODO add the possibility to select the default driver
    if (!empty($this->params)){
      foreach($this->params as $driverId=>$params){
        if (!empty($params['server'])){
          return $this->getScorerInstance(substr($driverId,7));
        }
      }
    }
    throw new NotImplementedException('No configured scorer driver found!');
  }

}