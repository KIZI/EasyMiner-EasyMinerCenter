<?php

namespace EasyMinerCenter\Model\Mining;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\TaskState;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;

/**
 * Class IMiningDriver - rozhraní pro unifikaci práce s dataminingovými nástroji
 * @package EasyMinerCenter\Model\mining
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IMiningDriver {

  /**
   * Method for starting of mining of the current task
   * @return TaskState
   */
  public function startMining();

  /**
   * Method for stopping of the current task
   * @return TaskState
   */
  public function stopMining();

  /**
   * Method for checking the state of the current task
   * @return TaskState
   */
  public function checkTaskState();


  /**
   * Method for full import of task results in PMML
   * @return TaskState
   */
  public function importResultsPMML();

  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param RulesFacade $rulesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params = array() - parametry výchozí konfigurace
   */
  public function __construct(Task $task=null, MinersFacade $minersFacade, RulesFacade $rulesFacade,MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array());

  /**
   * Method for checking, if the remote mining server is available
   * @param string $serverUrl
   * @throws \Exception
   * @return bool
   */
  public static function checkMinerServerState($serverUrl);

  /**
   * Method for setting the current (active) task
   * @param Task $task
   */
  public function setTask(Task $task);

  /**
   * Method for checking the configuration of the miner (including config params etc.)
   * @param User $user
   */
  public function checkMinerState(User $user);

  /**
   * Method for deleting the remote miner instance (on the remote mining server)
   * @return mixed
   */
  public function deleteMiner();

} 