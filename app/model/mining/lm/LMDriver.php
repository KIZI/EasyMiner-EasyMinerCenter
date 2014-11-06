<?php
namespace App\Model\Mining\LM;


use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\Mining\IMiningDriver;

class LMDriver implements IMiningDriver{
  /** @var  Task $task */
  private $task;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @param string $taskConfigJson
   */
  public function startMining($taskConfigJson) {
    // TODO: Implement startMining() method.
  }

  /**
   * Funkce pro zastavení dolování
   * @return bool
   */
  public function stopMining() {
    // TODO: Implement stopMining() method.
  }

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @return string
   */
  public function taskState() {
    // TODO: Implement taskState() method.
  }

  /**
   * Funkce pro načtení výsledků z DM nástroje a jejich uložení do DB
   */
  public function importResults() {
    // TODO: Implement importResults() method.
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @param Miner|Task $miner
   * @return mixed
   */
  public function checkMinerState($miner){
    // TODO: Implement checkMinerState() method.
  }

  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
  }

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   * @return mixed
   */
  public function setTask(Task $task) {
    $this->task=$task;
  }


}