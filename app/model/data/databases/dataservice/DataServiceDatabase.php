<?php

namespace EasyMinerCenter\Model\Data\Databases\DataService;

use EasyMinerCenter\Model\Data\Databases\DbField;
use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbColumn;
use EasyMinerCenter\Model\Data\Entities\DbColumnValuesStatistic;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;

/**
 * Class DataServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Data
 *
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 */
/*TODO add: abstract*/ class DataServiceDatabase implements IDatabase {

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return \EasyMinerCenter\Model\Data\Databases\Entities\DbDatasource[]
   */
  public function getDbDatasources() {
    // TODO: Implement getDbDatasources() method.
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param \EasyMinerCenter\Model\Data\Databases\Entities\DbDatasource $dbDatasource
   * @return DbField[]
   */
  public function getDbFields(\EasyMinerCenter\Model\Data\Databases\Entities\DbDatasource $dbDatasource) {
    // TODO: Implement getDbFields() method.
  }

  /**
   * Konstruktor zajišťující připojení k databázi
   *
   * @param DbConnection $dbConnection
   * @param string $apiKey
   * @return IDatabase
   */
  public function __construct(DbConnection $dbConnection, $apiKey) {
    // TODO: Implement __construct() method.
}}