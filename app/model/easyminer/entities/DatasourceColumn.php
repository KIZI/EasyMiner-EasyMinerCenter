<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entita zachycující mapování konkrétního datového sloupce...
 * @package App\Model\EasyMiner\Entities
 * @property int|null $datasourceColumnId=null
 * @property Datasource $datasource m:hasOne
 * @property string $name
 * @property string $type m:Enum('string','int','float')
 * @property int|null $strLen = null
 * @property string $formatId
 */
class DatasourceColumn extends Entity{
  const TYPE_STRING='string';
  const TYPE_INTEGER='int';
  const TYPE_FLOAT='float';
} 