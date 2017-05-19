<?php

namespace EasyMinerCenter\Model\Scoring;

/**
 * Class ScoringResult - class for representation fo test results of a ruleset or a task in form of CONFUSION MATRIX
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ScoringConfusionMatrix {
  private $labelsArr;
  private $rowsArr;
  private $scoredRowsCount;

  /**
   * @param string[] $labelsArr
   * @param int[][] $rowsArr
   * @param int $scoredRowsCount=0 - if it is 0, the value will be counted from the table
   */
  public function __construct($labelsArr, $rowsArr, $scoredRowsCount=0) {
    $this->labelsArr=$labelsArr;
    $this->rowsArr=$rowsArr;
    $this->scoredRowsCount=$scoredRowsCount;
  }

  /**
   * Method for merging of another confusion matrix to this one
   * @param ScoringConfusionMatrix $confusionMatrix2
   */
  public function mergeScoringConfusionMatrix(ScoringConfusionMatrix $confusionMatrix2) {
    //FIXME implement
  }

  /**
   * Method for summing of values in the table
   * @param bool $nullValuesIncluded=true
   * @return ScoringResult
   */
  public function getScoringResult($nullValuesIncluded=true) {
    $truePositive=0;
    $falsePositive=0;
    $trueNegative=0;
    $falseNegative=0;
    /** @var int[] $ignoredLabelsPositions - array for identification of ignored positions in the table*/
    $ignoredLabelsPositions=[];
    if (!empty($this->labelsArr) && !empty($this->rowsArr)){

      //region sum rows with the same values (invalid works with numbers) - TODO issue KIZI/EasyMiner-EasyMinerCenter#153
      $labelMappings=[];
      for ($labelI=0;$labelI<count($this->labelsArr);$labelI++){
        if (is_numeric($this->labelsArr[$labelI])){
          for ($i=$labelI+1;$i<count($this->labelsArr);$i++){
            if (isset($this->labelsArr[$i]) && is_numeric($this->labelsArr[$i]) && floatval($this->labelsArr[$i]==$this->labelsArr[$labelI])){
              $labelMappings[$i]=$labelI;
            }
          }
        }
      }
      if (!empty($labelMappings)){
        //sum the appropriate rows and then the appropriate columns
        foreach($labelMappings as $duplikatI => $hlavniI){
          foreach($this->rowsArr[$duplikatI] as $column=>$value){
            $this->rowsArr[$hlavniI][$column]+=$value;
          }
          unset($this->rowsArr[$duplikatI]);
        }
        //sum the appropriate columns
        $odstranitSloupce=[];
        foreach($labelMappings as $duplikatI => $hlavniI){
          foreach($this->rowsArr as &$row){
            $row[$hlavniI]+=$row[$duplikatI];
          }
          $odstranitSloupce[]=$duplikatI;
        }
        sort($odstranitSloupce);
        $odstranitSloupce=array_reverse($odstranitSloupce);
        foreach($this->rowsArr as &$row){
          foreach($odstranitSloupce as $columnI){
            unset($row[$columnI]);
          }
        }
      }
      //region sum rows with the same values (invalid works with numbers) - issue KIZI/EasyMiner-EasyMinerCenter#153

      //identify ignored positions in the table
      if($nullValuesIncluded){
        for ($labelI=0;$labelI<count($this->labelsArr); $labelI++){
          if ($this->labelsArr[$labelI]=='null'||$this->labelsArr[$labelI]==''){
            $ignoredLabelsPositions[]=$labelI;
          }
        }
      }
      //calculate values in individual rows
      foreach ($this->rowsArr as $rowI=>$rowData){
        $nullRow=(in_array($rowI,$ignoredLabelsPositions));
        if ($nullRow){
          //it is null row
          foreach($rowData as $columnI=>$value){
            if ($columnI==$rowI){
              $trueNegative+=$value;
            }elseif(in_array($columnI,$ignoredLabelsPositions)){
              $trueNegative+=$value;
            }else{
              $falsePositive+=$value;
            }
          }
        }else{
          //it is not a null row
          foreach($rowData as $columnI=>$value){
            if ($columnI==$rowI){
              $truePositive+=$value;
            }elseif(in_array($columnI,$ignoredLabelsPositions)){
              $falseNegative+=$value;
            }else{
              $falsePositive+=$value;
            }
          }
        }
      }
    }
    return new ScoringResult($truePositive, $falsePositive, $trueNegative, $falseNegative, max($this->scoredRowsCount, $truePositive+$falsePositive+$trueNegative+$falseNegative));
  }

}