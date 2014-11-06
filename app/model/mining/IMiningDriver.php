<?php

namespace App\Model\Mining;
use App\Model\EasyMiner\Entities\Task;

/**
 * Class IMiningDriver - rozhraní pro unifikaci práce s dataminingovými nástroji
 * @package App\Model\mining
 */
interface IMiningDriver {

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @param string $taskConfigJson
   */
  public function startMining($taskConfigJson);

  /**
   * Funkce pro zastavení dolování
   * @return bool
   */
  public function stopMining();

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @return string
   */
  public function taskState();

  /**
   * Funkce pro načtení výsledků z DM nástroje a jejich uložení do DB
   */
  public function importResults();

  /**
   * @param Task $task
   */
  public function __construct(Task $task);



  //TODO funkce pro přidání atributu
} 