<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Datasource
 * @package App\Model\EasyMiner\Entities
 * @property int|null $datasourceId = null
 * @property int|null $userId = null
 * @property string $type = m:Enum('mysql','cassandra')
 * @property string $dbServer
 * @property string $dbPort
 * @property string $dbUsername
 * @property string $dbPassword
 * @property string $dbName
 * @property string $dbTable
 */
class Datasource extends Entity{
  /**
   * Funkce vracející přehled typů databází
   * @return array
   */
  public static function getTypes(){
    return array(
      'mysql'=>'MySQL',
      'cassandra'=>'Cassandra DB'
    );
  }

} 