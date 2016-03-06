<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;

/**
 * Class LimitedDatabase - přístup k LIMITED DB pomocí EasyMiner-Data
 *
 * @package EasyMinerCenter\Model\Preprocessing\Databases\DataService
 * @author Stanislav Vojíř
 */
class LimitedDatabase extends PreprocessingServiceDatabase{

  const PP_TYPE=PpConnection::TYPE_LIMITED;
  const PP_TYPE_NAME=PpConnection::TYPE_LIMITED_NAME;

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