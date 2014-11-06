<?php
namespace App\Model\Mining;


use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use Nette\ArgumentOutOfRangeException;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class MiningDriverFactory - třída zajišťující vytvoření odpovídajícího driveru pro dolování
 * @package App\Model\Mining
 */
class MiningDriverFactory extends Object{
  const DRIVER_LM='\App\Model\Mining\LM\LMDriver';//TODO doplnění adres tříd
  const DRIVER_R='\App\Model\Mining\R\RDriver';

  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @return IMiningDriver
   * @throws ArgumentOutOfRangeException
   */
  public static function getDriverInstance(Task $task ,MinersFacade $minersFacade){
    $reflection=self::getReflection();
    if ($driverClass=$reflection->getConstant('DRIVER_'.Strings::upper($task->type))){
      return new $driverClass($task, $minersFacade);
    }
    throw new ArgumentOutOfRangeException('Requested mining driver was not found!',500);
  }

} 