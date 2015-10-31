<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;

/**
 * Class Preprocessing
 *
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $preprocessingId
 * @property Format $format m:hasOne
 * @property string $name
 * @property string $specialType = ''
 * @property User|null $user m:hasOne
 * @property bool $shared = false
 * @property ValuesBin[] $valuesBins m:hasMany
 * @property Attribute[] $generatedAttributes m:belongsToMany
 */
class Preprocessing extends Entity{

  const SPECIALTYPE_EACHONE='eachOne';
  const NEW_PREPROCESSING_EACHONE_NAME="Each value - one bin";

  /**
   * Funkce vracející seznam speciálních typů preprocessingu
   * @return string[]
   */
  public static function getSpecialTypes() {
    return [self::SPECIALTYPE_EACHONE];
  }

  /**
   * Funkce vracející Value nebo ValuesBin
   * @param string $valueName
   * @return Value|ValuesBin|null
   */
  public function findValue($valueName) {
    if ($this->specialType==self::SPECIALTYPE_EACHONE) {
      return $value=$this->format->findValueByValue($valueName);
    }else{
      return $this->findValuesBinByName($valueName);
    }
  }

  /**
   * @param string $valueBinName
   * @return ValuesBin|null
   */
  public function findValuesBinByName($valueBinName){
    $valuesBin = $this->getValueByPropertyWithRelationship('valuesBins', new Filtering(function (Fluent $statement) use ($valueBinName) {
      $statement->where("name = %s", $valueBinName);
    }));
    if (is_array($valuesBin)){
      return array_shift($valuesBin);
    }else{
      return $valuesBin;
    }
  }


} 