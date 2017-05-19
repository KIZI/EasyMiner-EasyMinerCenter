<?php

namespace EasyMinerCenter\Model\Data\Entities;
use Nette\NotSupportedException;

/**
 * Class DbConnection
 * @package EasyMinerCenter\Model\Data\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property string $type = m:Enum("mysql","limited","unlimited")
 * @property string|null $dbServer = null
 * @property string|null $dbApi = null
 * @property string $dbPort
 * @property string $dbUsername
 * @property string $dbPassword
 * @property string $dbName
 */
class DbConnection{
  const TYPE_MYSQL='mysql';
  const TYPE_LIMITED='limited';
  const TYPE_UNLIMITED='unlimited';
  const TYPE_MYSQL_NAME='MySQL';
  const TYPE_LIMITED_NAME='Limited DB (recommended)';
  const TYPE_UNLIMITED_NAME='Unlimited DB';

  /**
   * Function returning a connection string inspired by connection string for PDO, but including some properties of DbConnection
   * @return string
   */
  public function getConnectionString() {
    return $this->type.(!empty($this->dbName)?':dbname='.$this->dbName:'').(!empty($this->dbServer)?';host='.$this->dbServer:'').(!empty($this->dbApi)?';api='.$this->dbApi:'').';port='.$this->dbPort.';charset=utf8;user='.$this->dbUsername;
  }

  /**
   * Function returning PDO connection string
   * @return string
   * @throws NotSupportedException
   */
  public function getPDOConnectionString() {
    if ($this->type==self::TYPE_MYSQL){
      return 'mysql:host='.$this->dbServer.';'.(!empty($this->port)?'port='.$this->port.';':'').(!empty($this->dbName)?'dbname='.$this->dbName.';':'').'charset=utf8';
    }else{
      throw new NotSupportedException('PDO connection is not available for DB type '.$this->type);
    }
  }

} 