<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;

/**
 * Class LimitedDatabase - access to LIMITED DB using EasyMiner-Preprocessing
 * @package EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class LimitedDatabase extends PreprocessingServiceDatabase{

  const PP_TYPE=PpConnection::TYPE_LIMITED;
  const PP_TYPE_NAME=PpConnection::TYPE_LIMITED_NAME;

  #region methods returning identification constants
  /**
   * Method returning user understandable name of database
   * @return string
   */
  public static function getPpTypeName() {
    return self::PP_TYPE;
  }

  /**
   * Method returning identification of the database type
   * @return string
   */
  public static function getPpType() {
    return self::PP_TYPE_NAME;
  }
  #endregion methods returning identification constants
}