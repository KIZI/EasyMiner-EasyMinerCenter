<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbValuesRows - třída představující jednotlivé řádky získané z databáze
 * @package EasyMinerCenter\Model\Data\Entities
 * @author Stanislav Vojíř
 */
class DbValuesRows{
  /** @var  DbField[] $dbFields */
  private $dbFields;
  /** @var  array $valuesRows */
  private $valuesRows;

  /**
   * DbValuesRows constructor.
   * @param DbField[] $dbFields
   * @param array $valuesRows
   */
  public function __construct($dbFields, $valuesRows){
    $this->dbFields=$dbFields;
    $this->valuesRows=$valuesRows;
  }

  /**
   * Funkce vracející jednotlivé sloupce
   * @return DbField[]
   */
  public function getFields(){
    return $this->dbFields;
  }

  /**
   * Funkce vracející názvy jednotlivých sloupců
   * @return string[]
   */
  public function getFieldNames(){
    $names=[];
    if (!empty($this->dbFields)){
      foreach($this->dbFields as $dbField){
        $names[]=$dbField->name;
      }
    }
    return $names;
  }

  /**
   * Funkce vracející data z jednotlivých řádků
   * @return array
   */
  public function getValuesRows(){
    return $this->valuesRows;
  }
}