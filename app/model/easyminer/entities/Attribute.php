<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entita zachycující mapování konkrétního datového sloupce...
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $attributeId=null
 * @property Metasource $metasource m:hasOne
 * @property int|null $ppDatasetAttributeId = null
 * @property string $name
 * @property string|null $type m:Enum(self::TYPE_*)
 * @property DatasourceColumn $datasourceColumn m:hasOne
 * @property Preprocessing $preprocessing m:hasOne
 * @property bool $active
 */
class Attribute extends Entity{
  const TYPE_STRING='string';
  const TYPE_INTEGER='int';
  const TYPE_FLOAT='float';

  /**
   * Funkce vracející přehled základních dat
   * @return array
   */
  public function getDataArr() {
    return [
      'id'=>$this->attributeId,
      'name'=>$this->name,
      'preprocessing'=>$this->preprocessing->preprocessingId,
      'column'=>$this->datasourceColumn->getDataArr()
    ];
  }
} 