<?php

namespace EasyMinerCenter\Model\Mining;

use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTaskState;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Mining\Entities\Outlier;

/**
 * Interface IOutliersMiningDriver - unified interface of drivers for data mining of outliers
 * @package EasyMinerCenter\Model\Mining
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IOutliersMiningDriver{

  /**
   * Method for start of the current data mining task
   * @return OutliersTaskState
   */
  public function startMining();

  /**
   * Method for checking the current task state
   * @return OutliersTaskState
   */
  public function checkOutliersTaskState();

  /**
   * IOutliersMiningDriver constructor
   * @param OutliersTask $outliersTask
   * @param MinersFacade $minersFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params = array() - parametry výchozí konfigurace
   */
  public function __construct(OutliersTask $outliersTask=null, MinersFacade $minersFacade, MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array());

  /**
   * Method for checking, if the remote mining server is available
   * @param string $serverUrl
   * @throws \Exception
   * @return bool
   */
  public static function checkMinerServerState($serverUrl);

  /**
   * Method for setting the active (current) task
   * @param OutliersTask $outliersTask
   */
  public function setOutliersTask(OutliersTask $outliersTask);

  /**
   * Method for removing the current task
   * @return bool
   */
  public function deleteOutliersTask();

  /**
   * Method for deleting the current miner
   * @return mixed
   */
  public function deleteMiner();

  /**
   * Method returning results of the current task
   * @param int $limit
   * @param int $offset=0
   * @return Outlier[]
   */
  public function getOutliersTaskResults($limit,$offset=0);
}