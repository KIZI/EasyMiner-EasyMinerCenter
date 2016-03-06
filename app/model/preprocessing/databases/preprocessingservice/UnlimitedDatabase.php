<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;

use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;

/**
 * Class UnlimitedDatabase - přístup k UNLIMITED DB pomocí EasyMiner-Data
 * @package EasyMinerCenter\app\model\data\databases
 * @author Stanislav Vojíř
 */
class UnlimitedDatabase extends PreprocessingServiceDatabase{

  const PP_TYPE=PpConnection::TYPE_UNLIMITED;
  const PP_TYPE_NAME=PpConnection::TYPE_UNLIMITED_NAME;

  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getPpTypeName() {
    return self::PP_TYPE;
  }

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getPpType() {
    return self::PP_TYPE_NAME;
  }

}