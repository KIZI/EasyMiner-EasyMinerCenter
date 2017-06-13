<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;
use Nette\NotSupportedException;

/**
 * Class DbConnection
 * @package EasyMinerCenter\Model\Preprocessing\Entities
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
class PpConnection{
  const TYPE_MYSQL='mysql';
  const TYPE_LIMITED='limited';
  const TYPE_UNLIMITED='unlimited';
  const TYPE_MYSQL_NAME='MySQL';
  const TYPE_LIMITED_NAME='Limited preprocessing service DB';
  const TYPE_UNLIMITED_NAME='Unlimited preprocessing service DB';

  /**
   * Method returning connection string inspired by connection string used for PDO, but extended with all properties od DbConnection
   * @return string
   */
  public function getConnectionString() {
    return $this->type.':dbname='.$this->dbName.(!empty($this->dbServer)?';host='.$this->dbServer:'').(!empty($this->dbApi)?';api='.$this->dbApi:'').';port='.$this->dbPort.';charset=utf8;user='.$this->dbUsername;
  }

  /**
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