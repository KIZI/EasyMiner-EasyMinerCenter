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
 * Interface IOutliersMiningDriver - rozhraní pro unifikaci práce s dataminingovými nástroji pro detekci outlierů
 * @package EasyMinerCenter\Model\Mining
 * @author Stanislav Vojíř
 */
interface IOutliersMiningDriver{

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @return OutliersTaskState
   */
  public function startMining();

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @return OutliersTaskState
   */
  public function checkOutliersTaskState();

  /**
   * @param OutliersTask $outliersTask
   * @param MinersFacade $minersFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params = array() - parametry výchozí konfigurace
   */
  public function __construct(OutliersTask $outliersTask=null, MinersFacade $minersFacade, MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array());

  /**
   * Funkce pro kontrolu, jestli je dostupný dolovací server
   * @param string $serverUrl
   * @throws \Exception
   * @return bool
   */
  public static function checkMinerServerState($serverUrl);

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param OutliersTask $outliersTask
   */
  public function setOutliersTask(OutliersTask $outliersTask);

  /**
   * Funkce pro odstranění aktivní úlohy
   * @return bool
   */
  public function deleteOutliersTask();

  /**
   * Funkce volaná před smazáním konkrétního mineru
   * @return mixed
   */
  public function deleteMiner();

  /**
   * Funkce vracející výsledky úlohy dolování outlierů
   * @param int $limit
   * @param int $offset=0
   * @return Outlier[]
   */
  public function getOutliersTaskResults($limit,$offset=0);
}