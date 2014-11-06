<?php

namespace App\Model\Mining\R;


use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\Mining\IMiningDriver;

class RDriver implements IMiningDriver{
  /** @var  Task $task */
  private $task;
  /** @var MinersFacade $minersFacade */
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
   * @param Task $task
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade) {
    $this->minersFacade=$minersFacade;
    $this->task=$task;
  }

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   * @return mixed
   */
  public function setTask(Task $task) {
    $this->task=$task;
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @param Miner|Task $miner
   * @return bool
   */
  public function checkMinerState($miner) {
    return true;
  }
}