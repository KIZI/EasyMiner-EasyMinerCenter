<?php

namespace EasyMinerCenter\Model\Data\Entities;

use Nette;

/**
 * Class DbConnection
 * @package EasyMinerCenter\Model\Data\Entities
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

  /**
   * Funkce vracející connection string inspirovaný connection stringem pro PDO, ale obsahující všechny vlastnosti DbConnection
   * @return string
   */
  public function getConnectionString() {
    return $this->type.':dbname='.$this->dbName.(!empty($this->dbServer)?';host='.$this->dbServer:'').(!empty($this->dbApi)?';api='.$this->dbApi:'').';port='.$this->dbPort.';charset=utf8;user='.$this->dbUsername;
  }

} 