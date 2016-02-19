<?php

namespace EasyMinerCenter\Model\Data\Databases;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbField;

/**
 * Interface IDatabase - rozhraní definující funkce pro práci s různými datovými zdroji (pro zajištění nezávislosti na jedné DB
 * @package EasyMinerCenter\Model\Data\Databases
 */
interface IDatabase {

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return DbDatasource[]
   */
  public function getDbDatasources();

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param DbDatasource $dbDatasource
   * @return DbField[]
   */
  public function getDbFields(DbDatasource $dbDatasource);

  /**
   * Konstruktor zajišťující připojení k databázi
   *
   * @param DbConnection $dbConnection
   * @param string $apiKey
   * @return IDatabase
   */
  public function __construct(DbConnection $dbConnection, $apiKey);


} 