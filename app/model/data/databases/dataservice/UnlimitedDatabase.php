<?php

namespace EasyMinerCenter\Model\Data\Databases\DataService;

use EasyMinerCenter\Model\Data\Databases\DataService;
use EasyMinerCenter\Model\Data\Entities\DbConnection;

/**
 * Class UnlimitedDatabase - přístup k UNLIMITED DB pomocí EasyMiner-Data
 * @package EasyMinerCenter\app\model\data\databases
 * @author Stanislav Vojíř
 */
class UnlimitedDatabase extends DataServiceDatabase{

  const DB_TYPE=DbConnection::TYPE_UNLIMITED;
  const DB_TYPE_NAME=DbConnection::TYPE_UNLIMITED_NAME;

  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getDbTypeName() {
    return self::DB_TYPE_NAME;
  }

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getDbType() {
    return self::DB_TYPE;
  }

}