<?php
namespace EasyMinerCenter\Model\Mining;


use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializerFactory;
use Nette\ArgumentOutOfRangeException;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class MiningDriverFactory - třída zajišťující vytvoření odpovídajícího driveru pro dolování
 * @package EasyMinerCenter\Model\Mining
 */
class MiningDriverFactory extends Object{
  private $params;
  /** @var  GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory */
  private $guhaPmmlSerializerFactory;

  public function __construct($params, GuhaPmmlSerializerFactory $guhaPmmlSerializerFactory){
    $this->params=$params;
    $this->guhaPmmlSerializerFactory=$guhaPmmlSerializerFactory;
  }

  /**
   * Funkce vracející URL pro přístup ke zvolenému mineru
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
   * Funkce pro kontrolu, jestli je funkční vzdálený server zajištující dolování
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
   * Funkce pro vytvoření nové instance mineru
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param string $backgroundImportLink="" - relativní URL pro spuštění plného importu (na pozadí)
   * @return IMiningDriver
   */
  public function getDriverInstance(Task $task ,MinersFacade $minersFacade, RulesFacade $rulesFacade, MetaAttributesFacade $metaAttributesFacade, User $user,$backgroundImportLink=""){
    if (isset($this->params['driver_'.$task->type])){
      $driverClass='\\'.$this->params['driver_'.$task->type]['class'];
      return new $driverClass($task, $minersFacade, $rulesFacade, $metaAttributesFacade, $user, $this->guhaPmmlSerializerFactory, $this->params['driver_'.$task->type],$backgroundImportLink);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

} 