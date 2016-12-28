<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;

/**
 * Class Format
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 *
 * @property int $formatId
 * @property string $name
 * @property string $dataType m:Enum(self::TYPE_*)
 * @property MetaAttribute $metaAttribute m:hasOne
 * @property bool $shared = false
 * @property User|null $user m:hasOne
 *
 * @property Interval[] $intervals m:belongsToMany
 * @property Value[] $values m:belongsToMany
 * @property ValuesBin[] $valuesBins m:belongsToMany
 * @property Preprocessing[] $preprocessings m:belongsToMany
 */
class Format  extends Entity{
  const DATATYPE_VALUES='values';
  const DATATYPE_INTERVAL='interval';

  /**
   * @param string $value
   * @return Value
   */
  public function findValueByValue($value){
    $valuesItem = $this->getValueByPropertyWithRelationship('values', new Filtering(function (Fluent $statement) use ($value) {
      $statement->where("value = %s COLLATE utf8_bin", $value);
      $statement->limit(1);
    }));
    if (is_array($valuesItem)){
      return array_shift($valuesItem);
    }else{
      return $valuesItem;
    }
  }


  /**
   * @param string $valueBinName
   * @return ValuesBin|null
   */
  public function findValuesBinByName($valueBinName){
    $valuesBin = $this->getValueByPropertyWithRelationship('valuesBins', new Filtering(function (Fluent $statement) use ($valueBinName) {
      $statement->where("name = %s COLLATE utf8_bin", $valueBinName);
    }));
    if (is_array($valuesBin)){
      return array_shift($valuesBin);
    }else{
      return $valuesBin;
    }
  }

} 