<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use EasyMinerCenter\Model\Preprocessing\Entities\PpValue;

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
   * Funkce pro inicializaci preprocessind datasetu
   *
   * @param PpDataset|null $ppDataset = null
   * @param PpTask|null $ppTask = null
   * @return PpTask|PpDataset - při dokončení vytvoření úlohy vrací PpDataset, jinak PpTask
   */
  public function createPpDataset(PpDataset $ppDataset=null, PpTask $ppTask=null);

  /**
   * Funkce pro odstranění preprocessing datasetu
   *
   * @param PpDataset $ppDataset
   */
  public function deletePpDataset(PpDataset $ppDataset);

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   */
  public function getPpAttributes(PpDataset $ppDataset);

  /**
   * Funkce pro inicializaci preprocessingu atributů
   *
   * @param Attribute[] $attributes
   * @param PpTask $ppTask = null
   * @return PpTask|PpAttribute[]
   */
  public function createAttributes(array $attributes = null, PpTask $ppTask = null);

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

  /**
   * Funkce vracející hodnoty zvoleného atributu
   *
   * @param PpDataset $ppDataset
   * @param PpAttribute $ppAttribute
   * @param int $offset
   * @param int $limit
   * @return PpValue[]
   */
  public static function getPpValues(PpDataset $ppDataset, PpAttribute $ppAttribute, $offset=0, $limit=1000);
  
  
  /**
   * Funkce vracející přehled podporovaných typů preprocessingu
   *
   * @return string[]
   */
  public static function getSupportedPreprocessingTypes();

} 