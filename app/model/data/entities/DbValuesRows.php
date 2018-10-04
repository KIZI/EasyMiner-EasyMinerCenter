<?php

namespace EasyMinerCenter\Model\Data\Entities;

/**
 * Class DbValuesRows - class representing individual rows gained from the database
 * @package EasyMinerCenter\Model\Data\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
   * Function returning individual columns (DbFields)
   * @return DbField[]
   */
  public function getFields(){
    return $this->dbFields;
  }

  /**
   * Function returning names of individual columns (DbFields)
   * @return string[]
   */
  public function getFieldNames(){
    $names=[];
    if (!empty($this->dbFields)){
      foreach($this->dbFields as $dbField){
        $names[$dbField->id]=$dbField->name;
      }
    }
    return $names;
  }

  /**
   * Function returning data from individual DB rows
   * @param bool $includeEmptyFields = false
   * @return array
   */
  public function getValuesRows($includeEmptyFields=false){
    if (!$includeEmptyFields){
      return $this->valuesRows;
    }else{
      $result=[];
      if (!empty($this->valuesRows)){
        foreach ($this->valuesRows as $valuesRowId=>$valuesRow){
          $rowResult=[];
          if (!empty($this->dbFields)){
            foreach ($this->dbFields as $dbField){
              $rowResult[$dbField->id]=(!empty($valuesRow[$dbField->id])?$valuesRow[$dbField->id]:'');
            }
          }
          $result[$valuesRowId]=$rowResult;
        }
      }
      return $result;
    }
  }

  /**
   * Function for composition of the data from DB row to the form of objects in JSON
   * @return array
   */
  public function getRowsAsArray(){
    $fieldNames=$this->getFieldNames();
    $result=[];
    if (!empty($this->valuesRows)){
      foreach($this->valuesRows as $valuesRow){
        $rowArr=[];
        foreach($fieldNames as $i=>$fieldName){
          if (isset($valuesRow[$i])){
            $rowArr[$fieldName]=$valuesRow[$i];
          }
        }
        $result[]=$rowArr;
      }
    }
    return $result;
  }
}