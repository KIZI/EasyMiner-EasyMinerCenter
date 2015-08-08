<?php
namespace EasyMinerCenter\Model\Mining;


use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use Nette\ArgumentOutOfRangeException;
use Nette\Object;

/**
 * Class MiningDriverFactory - třída zajišťující vytvoření odpovídajícího driveru pro dolování
 * @package EasyMinerCenter\Model\Mining
 */
class MiningDriverFactory extends Object{
  private $params;

  public function __construct($params){
    $this->params=$params;
  }

  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @return IMiningDriver
   */
  public function getDriverInstance(Task $task ,MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade){
    if (isset($this->params['driver_'.$task->type])){
      $driverClass='\\'.$this->params['driver_'.$task->type]['class'];
      return new $driverClass($task, $minersFacade, $rulesFacade, $metaAttributesFacade, $this->params['driver_'.$task->type]);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

} 