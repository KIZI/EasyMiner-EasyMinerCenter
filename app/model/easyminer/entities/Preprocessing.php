<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class Preprocessing
 *
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $preprocessingId
 * @property Format $format m:hasOne
 * @property string $name
 * @property string $specialType = ''
 * @property string|null $specialTypeParams=null
 * @property User|null $user m:hasOne
 * @property bool $shared = false
 * @property ValuesBin[] $valuesBins m:hasMany
 * @property Attribute[] $generatedAttributes m:belongsToMany
 * @method addToValuesBins
 * @method removeFromValuesBins
 */
class Preprocessing extends Entity{

  const SPECIALTYPE_EACHONE='eachOne';
  const SPECIALTYPE_EQUIFREQUENT_INTERVALS='equifrequentIntervals';
  const SPECIALTYPE_EQUISIZED_INTERVALS='equisizedIntervals';
  const SPECIALTYPE_EQUIDISTANT_INTERVALS='equidistantIntervals';
  const NEW_PREPROCESSING_EACHONE_NAME="Each value - one bin";

  const TYPE_EACHONE=self::SPECIALTYPE_EACHONE;
  const TYPE_NOMINAL_ENUMERATION='nominalEnumeration';
  const TYPE_INTERVAL_ENUMERATION='intervalEnumeration';
  const TYPE_EQUIDISTANT_INTERVALS=self::SPECIALTYPE_EQUIDISTANT_INTERVALS;
  const TYPE_EQUIFREQUENT_INTERVALS=self::SPECIALTYPE_EQUIFREQUENT_INTERVALS;
  const TYPE_EQUISIZED_INTERVALS=self::SPECIALTYPE_EQUISIZED_INTERVALS;


  /**
   * Funkce vracející seznam speciálních typů preprocessingu
   * @return string[]
   */
  public static function getSpecialTypes() {
    return [self::SPECIALTYPE_EACHONE, self::TYPE_EQUIFREQUENT_INTERVALS];
  }

  /**
   * Funkce vracející definovatelné typy preprocessingu
   * @return string[]
   */
  public static function getPreprocessingTypes(){
    return [self::TYPE_EACHONE,self::TYPE_NOMINAL_ENUMERATION,self::TYPE_INTERVAL_ENUMERATION,self::TYPE_EQUIDISTANT_INTERVALS,self::TYPE_EQUIFREQUENT_INTERVALS];
  }

  /**
   * Funkce pro dekódování alternativních podporovaných názvů typů preprocessingu
   * @param string $preprocessingType
   * @return string
   */
  public static function decodeAlternativePrepreprocessingTypeIdentification($preprocessingType){
    $preprocessingTypeLowerCase=Strings::lower($preprocessingType);
    switch($preprocessingTypeLowerCase){
      case "nominal":
      case "bins":
        return self::TYPE_NOMINAL_ENUMERATION;
      case "intervals":
        return self::TYPE_INTERVAL_ENUMERATION;
      case "equidistant":
      case "equidistantInterval":
        return self::TYPE_EQUIDISTANT_INTERVALS;
      case "equifrequent":
      case "equifrequentInterval":
        return self::TYPE_EQUIFREQUENT_INTERVALS;
    }
    return $preprocessingType;
  }

  /**
   * Funkce vracející parametry speciálního preprocessingu
   * @return array
   */
  public function getSpecialTypeParams(){
    /** @noinspection PhpUndefinedFieldInspection */
    if (!empty($this->row->special_type_params)){
      /** @noinspection PhpUndefinedFieldInspection */
      return Json::decode($this->row->special_type_params,Json::FORCE_ARRAY);
    }else{
      return [];
    }
  }

  /**
   * Funkce ukládající parametry speciálního preprocessingu
   * @param array|null $params
   */
  public function setSpecialTypeParams(array $params){
    if (!empty($params)){
      /** @noinspection PhpUndefinedFieldInspection */
      $this->row->special_type_params=Json::encode($params);
    }else{
      /** @noinspection PhpUndefinedFieldInspection */
      $this->row->special_type_params=null;
    }
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
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $statement->where("name = %s", $valueBinName);
    }));
    if (is_array($valuesBin)){
      return array_shift($valuesBin);
    }else{
      return $valuesBin;
    }
  }


} 