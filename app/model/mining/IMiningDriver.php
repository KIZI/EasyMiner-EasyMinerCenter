<?php

namespace App\Model\Mining;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;

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
   * @param MinersFacade $minersFacade
   * @param array $params = array() - parametry výchozí konfigurace
   */
  public function __construct(Task $task=null, MinersFacade $minersFacade, $params = array());

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   */
  public function setTask(Task $task);

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   */
  public function checkMinerState();

  /**
   * Funkce volaná po smazání konkrétního mineru
   * @return mixed
   */
  public function deletedMiner();

} 