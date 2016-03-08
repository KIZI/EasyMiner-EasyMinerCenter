<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;

/**
 * Interface IDatabase - rozhraní definující funkce pro práci s různými datovými zdroji (pro zajištění nezávislosti na jedné DB
 *
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 */
interface IPreprocessing {

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return PpDataset[]
   */
  public function getPpDatasets();

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   *
   * @param int|string $ppDatasetId
   * @return PpDataset
   */
  public function getPpDataset($ppDatasetId);

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   */
  public function getPpAttributes(PpDataset $ppDataset);

  /**
   * Konstruktor zajišťující připojení k databázi
   *
   * @param PpConnection $ppConnection
   * @param string $apiKey
   * @return IPreprocessing
   */
  public function __construct(PpConnection $ppConnection, $apiKey);


  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getPpTypeName();

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getPpType();

} 