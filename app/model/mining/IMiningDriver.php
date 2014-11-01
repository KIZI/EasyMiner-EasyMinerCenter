<?php

namespace App\Model\Mining;

/**
 * Class IMiningDriver - rozhraní pro unifikaci práce s dataminingovými nástroji
 * @package App\Model\mining
 */
interface IMiningDriver {

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @param string $taskId
   * @param string $taskConfigJson
   */
  public function startMining($taskId,$taskConfigJson);

  /**
   * Funkce pro zastavení dolování
   * @param string $taskId
   * @return bool
   */
  public function stopMining($taskId);

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @param string $taskId
   * @return string
   */
  public function taskState($taskId);

  /**
   * Funkce pro načtení výsledků z DM nástroje a jejich uložení do DB
   * @param string $taskId
   */
  public function importResults($taskId);

  //TODO funkce pro přidání atributu
} 