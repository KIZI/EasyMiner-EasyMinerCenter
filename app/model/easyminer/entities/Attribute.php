<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class Attribute - entity representing one attribute
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
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
  //TODO pročistit seznam datových typů
  const TYPE_NOMINAL='nominal';
  const TYPE_NUMERIC='numeric';

  /**
   * Method returning an array with basic data properties
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