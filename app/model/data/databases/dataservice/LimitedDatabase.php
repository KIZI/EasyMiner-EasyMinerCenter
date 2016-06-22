<?php

namespace EasyMinerCenter\Model\Data\Databases\DataService;
use EasyMinerCenter\Model\Data\Entities\DbConnection;

/**
 * Class LimitedDatabase - přístup k LIMITED DB pomocí EasyMiner-Data
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 */
class LimitedDatabase extends DataServiceDatabase{

  const DB_TYPE=DbConnection::TYPE_LIMITED;
  const DB_TYPE_NAME=DbConnection::TYPE_LIMITED_NAME;

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