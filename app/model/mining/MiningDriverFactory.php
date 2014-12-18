<?php
namespace App\Model\Mining;


use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\EasyMiner\Facades\RulesFacade;
use Nette\ArgumentOutOfRangeException;
use Nette\Object;

/**
 * Class MiningDriverFactory - třída zajišťující vytvoření odpovídajícího driveru pro dolování
 * @package App\Model\Mining
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
   * @return IMiningDriver
   */
  public function getDriverInstance(Task $task ,MinersFacade $minersFacade, RulesFacade $rulesFacade){
    if (isset($this->params['driver_'.$task->type])){
      $driverClass='\\'.$this->params['driver_'.$task->type]['class'];
      return new $driverClass($task, $minersFacade, $rulesFacade, $this->params['driver_'.$task->type]);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

} 