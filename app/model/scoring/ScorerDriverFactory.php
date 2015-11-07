<?php

namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use Nette\Application\LinkGenerator;
use Nette\ArgumentOutOfRangeException;
use Nette\NotImplementedException;

/**
 * Class ScorerDriverFactory
 * @package EasyMinerCenter\Model\Scoring
 */
class ScorerDriverFactory {
  /** @var  array $params */
  private $params;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;

  /**
   * @param array $params
   * @param DatabasesFacade $databasesFacade
   */
  public function __construct($params, DatabasesFacade $databasesFacade) {
    $this->params=$params;
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * Funkce vracející instanci konkrétního ScorerDriveru
   * @param string $scorerType
   * @return IScorerDriver
   */
  public function getScorerInstance($scorerType) {
    /** @var array $driverConfigParams */
    $driverConfigParams=@$this->params['driver_'.$scorerType];
    if (empty($driverConfigParams) || !isset($driverConfigParams['class'])){
      throw new ArgumentOutOfRangeException('Requested scorer driver was not found!');
    }
    $driverClass='\\'.$this->params['driver_'.$scorerType]['class'];
    $result=new $driverClass($driverConfigParams['server'], $this->databasesFacade,$driverConfigParams);
    return $result;
  }

  /**
   * Funkce vracející výchozí driver (respektive první, u kterého je nalezena konfigurace)
   * @return IScorerDriver
   * @throws NotImplementedException
   */
  public function getDefaultScorerInstance(){//TODO doplnit možnost vybrání výchozího driveru
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