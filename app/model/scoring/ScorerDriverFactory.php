<?php

namespace EasyMinerCenter\Model\Scoring;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use Nette\ArgumentOutOfRangeException;

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
    return new $driverClass($driverConfigParams['server'], $this->databasesFacade);
  }

}