<?php

namespace EasyMinerCenter\Model\Scoring;

/**
 * Class ScoringResult - třída pro záznam výsledků testování rulesetu či úlohy v podobě CONFUSION MATRIX
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 */
class ScoringConfusionMatrix {
  private $labelsArr;
  private $rowsArr;
  private $scoredRowsCount;

  /**
   * @param string[] $labelsArr
   * @param int[][] $rowsArr
   * @param int $scoredRowsCount
   */
  public function __construct($labelsArr, $rowsArr, $scoredRowsCount) {
    $this->labelsArr=$labelsArr;
    $this->rowsArr=$rowsArr;
    $this->scoredRowsCount=$scoredRowsCount;
  }

  /**
   * Funkce pro vysčítání hodnot v tabulce
   * @param bool $ignoreNullValues=true
   * @return ScoringResult
   */
  public function getScoringResult($ignoreNullValues=true) {
    $truePositive=0;
    $falsePositive=0;
    /** @var int[] $ignoredLabelsPositions - pole pro určení těch pozic v tabulce, které mají být ignorovány */
    $ignoredLabelsPositions=[];
    if (!empty($this->labelsArr) && !empty($this->rowsArr)){
      //určení ignorování konkrétních pozic v tabulce
      if ($ignoreNullValues){
        for ($labelI=0;$labelI<count($this->labelsArr); $labelI++){
          if ($this->labelsArr[$labelI]=='null'||$this->labelsArr[$labelI]==''){
            $ignoredLabelsPositions[]=$labelI;
          }
        }
      }
      //spočítání hodnot v jednotlivých řádcích
      foreach ($this->rowsArr as $rowI=>$rowData){
        if (in_array($rowI,$ignoredLabelsPositions)){continue;}
        foreach($rowData as $columnI=>$value){
          if ($columnI==$rowI){
            $truePositive+=$value;
          }elseif(!in_array($columnI,$ignoredLabelsPositions)){
            $falsePositive+=$value;
          }
        }
      }
    }
    return new ScoringResult($truePositive, $falsePositive, $this->scoredRowsCount);
  }

}