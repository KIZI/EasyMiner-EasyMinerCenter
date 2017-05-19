<?php

namespace EasyMinerCenter\Model\Data\Databases\DataService;

use EasyMinerCenter\Model\Data\Entities\DbConnection;

/**
 * Class UnlimitedDatabase - driver for access to UNLIMITED DB using EasyMiner-Data
 * @package EasyMinerCenter\app\model\data\databases
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class UnlimitedDatabase extends DataServiceDatabase{

  const DB_TYPE=DbConnection::TYPE_UNLIMITED;
  const DB_TYPE_NAME=DbConnection::TYPE_UNLIMITED_NAME;

  /**
   * Method returning user understandable name of this database
   * @return string
   */
  public static function getDbTypeName() {
    return self::DB_TYPE_NAME;
  }

  /**
   * Method returning identification of this database type
   * @return string
   */
  public static function getDbType() {
    return self::DB_TYPE;
  }

}