<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;

/**
 * Class Format
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
   * @return Value|null
   */
  public function findValueByValue($value){
    /** @var Value|Value[]|null $valuesItem */
    $valuesItem = $this->getValueByPropertyWithRelationship('values', new Filtering(function (Fluent $statement) use ($value) {
      /** @noinspection PhpMethodParametersCountMismatchInspection */
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
    /** @var ValuesBin|ValuesBin[]|null $valuesBin */
    $valuesBin = $this->getValueByPropertyWithRelationship('valuesBins', new Filtering(function (Fluent $statement) use ($valueBinName) {
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      $statement->where("name = %s COLLATE utf8_bin", $valueBinName);
    }));
    if (is_array($valuesBin)){
      return array_shift($valuesBin);
    }else{
      return $valuesBin;
    }
  }

  /**
   * Method returning complete definition range of this format
   * @return Interval|null
   */
  public function getAllIntervalsRange(){
    if (count($this->intervals)){
      $result=null;
      foreach($this->intervals as $interval){
        if (!$result instanceof Interval){
          $result=$interval;
          continue;
        }
        if ($interval->leftMargin<$result->leftMargin){
          $result->leftMargin=$interval->leftMargin;
          $result->leftClosure=$interval->leftClosure;
        }elseif($interval->leftMargin==$result->leftMargin && $interval->leftClosure==Interval::CLOSURE_CLOSED){
          $result->leftClosure=Interval::CLOSURE_CLOSED;
        }
        if ($interval->rightMargin<$result->rightMargin){
          $result->rightMargin=$interval->rightMargin;
          $result->rightClosure=$interval->rightClosure;
        }elseif($interval->rightMargin==$result->rightMargin && $interval->rightClosure==Interval::CLOSURE_CLOSED){
          $result->rightClosure=Interval::CLOSURE_CLOSED;
        }
      }
      return $result;
    }else{
      return null;
    }
  }

} 