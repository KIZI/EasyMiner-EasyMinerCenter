<?php

namespace EasyMinerCenter\Model\Data\Databases;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Entities\DbValue;
use EasyMinerCenter\Model\Data\Entities\DbValuesRows;

/**
 * Interface IDatabase - rozhraní definující funkce pro práci s různými datovými zdroji (pro zajištění nezávislosti na jedné DB
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 */
interface IDatabase {

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return DbDatasource[]
   */
  public function getDbDatasources();

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   *
   * @param int|string $datasourceId
   * @return DbDatasource
   */
  public function getDbDatasource($datasourceId);

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param DbDatasource $dbDatasource
   * @return DbField[]
   */
  public function getDbFields(DbDatasource $dbDatasource);

  /**
   * Funkce pro odstranění datového zdroje
   *
   * @param DbDatasource $dbDatasource
   */
  public function deleteDbDatasource(DbDatasource $dbDatasource);

  /**
   * Konstruktor zajišťující připojení k databázi
   *
   * @param DbConnection $dbConnection
   * @param string $apiKey
   * @return IDatabase
   */
  public function __construct(DbConnection $dbConnection, $apiKey);


  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getDbTypeName();

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getDbType();

  /**
   * Funkce pro přejmenování datového sloupce
   * @param DbField $dbField
   * @param string $newName='' (pokud není název vyplněn, je převzat název z DbField
   * @return bool
   */
  public function renameDbField(DbField $dbField, $newName='');

  /**
   * Funkce pro rozbalení komprimovaných dat
   * @param string $data
   * @param string $compression
   * @return string
   */
  public function unzipData($data, $compression);

  /**
   * Funkce vracející hodnoty zvoleného datového sloupce (DbField)
   *
   * @param DbField $dbField
   * @param int $offset
   * @param int $limit
   * @return DbValue[]
   */
  public function getDbValues(DbField $dbField, $offset=0, $limit=1000);

  /**
   * Funkce vracející jednotlivé řádky z databáze
   *
   * @param DbDatasource $dbDatasource
   * @param int $offset=0
   * @param int $limit=1000
   * @param DbField[]|null $preloadedDbFields
   * @return DbValuesRows
   */
  public function getDbValuesRows(DbDatasource $dbDatasource, $offset=0, $limit=1000, &$preloadedDbFields=null);

  /**
   * Funkce pro import existujícího CSV souboru do databáze
   *
   * @param string $filename
   * @param string $name
   * @param string $encoding
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string[] $dataTypes
   * @return DbDatasource
   */
  public function importCsvFile($filename, $name, $encoding='utf-8', $delimiter=',', $enclosure='"', $escapeCharacter='\\', $nullValue='', $dataTypes);
  
} 