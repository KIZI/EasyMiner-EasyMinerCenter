<?php
namespace EasyMinerCenter\Model\Mining;

use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use Nette\ArgumentOutOfRangeException;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class MiningDriverFactory - class with factory methods for mining drivers
 * @package EasyMinerCenter\Model\Mining
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MiningDriverFactory extends Object{
  private $params;
  /** @var  XmlSerializersFactory $xmlSerializersFactory */
  private $xmlSerializersFactory;

  public function __construct($params, XmlSerializersFactory $xmlSerializersFactory){
    $this->params=$params;
    $this->xmlSerializersFactory=$xmlSerializersFactory;
  }

  /**
   * Method returning URL of the selected remote mining server
   * @param string $minerType
   * @return string
   */
  public function getMinerUrl($minerType) {
    $url=@$this->params['driver_'.$minerType]['server'];
    if(!empty($url)&&!empty($this->params['driver_'.$minerType]['minerUrl'])){
      if (!Strings::endsWith($this->params['driver_'.$minerType]['server'],'/')){
        $url.=ltrim($this->params['driver_'.$minerType]['minerUrl'],'/');
      }
    }
    return $url;
  }

  /**
   * Method for check, if the remote mining server is available
   * @param string $minerType
   * @param string $minerServerUrl=""
   * @throws ArgumentOutOfRangeException
   * @throws \Exception
   * @return bool
   */
  public function checkMinerServerState($minerType, $minerServerUrl="") {
    if (isset($this->params['driver_'.$minerType])){
      /** @var IMiningDriver $driverClass */
      $driverClass='\\'.$this->params['driver_'.$minerType]['class'];
      if ($minerServerUrl==""){$minerServerUrl=$this->getMinerUrl($minerType);}
      return $driverClass::checkMinerServerState($minerServerUrl);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

  /**
   * Factory method returning new instance of mining driver for the given task (for association rules)
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param string $backgroundImportLink="" - relative URL for background import request (for full import of PMML)
   * @return IMiningDriver
   */
  public function getDriverInstance(Task $task ,MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade, User $user,$backgroundImportLink=""){
    if (isset($this->params['driver_'.$task->type])){
      $driverClass='\\'.(!empty($this->params['driver_'.$task->type]['rules_class'])?$this->params['driver_'.$task->type]['rules_class']:$this->params['driver_'.$task->type]['class']);
      return new $driverClass($task, $minersFacade, $rulesFacade, $metaAttributesFacade, $user, $this->xmlSerializersFactory, $this->params['driver_'.$task->type],$backgroundImportLink);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

  /**
   * Factory method returning new instance of mining driver for outlier detection
   * @param OutliersTask $outliersTask
   * @param MinersFacade $minersFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @return IOutliersMiningDriver
   */
  public function getOutlierDriverInstance(OutliersTask $outliersTask, MinersFacade $minersFacade, MetaAttributesFacade $metaAttributesFacade, User $user){
    if (isset($this->params['driver_'.$outliersTask->type])){
      $driverClass='\\'.(!empty($this->params['driver_'.$outliersTask->type]['outliers_class'])?$this->params['driver_'.$outliersTask->type]['outliers_class']:$this->params['driver_'.$outliersTask->type]['class']);
      $driver=new $driverClass($outliersTask, $minersFacade, $metaAttributesFacade, $user, $this->xmlSerializersFactory, $this->params['driver_'.$outliersTask->type]);
      if ($driver instanceof IOutliersMiningDriver){
        return $driver;
      }else{
        throw new ArgumentOutOfRangeException('Requested mining driver does not support outlier detection!',500);
      }
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

} 