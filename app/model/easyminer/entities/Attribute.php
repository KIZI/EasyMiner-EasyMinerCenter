<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entita zachycující mapování konkrétního datového sloupce...
 * @package App\Model\EasyMiner\Entities
 * @property int|null $attributeId=null
 * @property Metasource $metasource m:hasOne
 * @property string $name
 * @property string|null $type m:Enum('string','int','float')
 * @property DatasourceColumn $datasourceColumn m:hasOne
 * @property string $preprocessingId
 */
class Attribute extends Entity{
  const TYPE_STRING='string';
  const TYPE_INTEGER='int';
  const TYPE_FLOAT='float';
} 