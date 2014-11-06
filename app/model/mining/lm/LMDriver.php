<?php
namespace App\Model\Mining\LM;


use App\Model\EasyMiner\Entities\Task;
use App\Model\Mining\IMiningDriver;

class LMDriver implements IMiningDriver{

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
  public function __construct(Task $task) {
    // TODO: Implement __construct() method.
  }
}