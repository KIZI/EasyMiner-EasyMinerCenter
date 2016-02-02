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
   * @param int $scoredRowsCount=0 - pokud je 0, bude hodnota spočítána z tabulky
   */
  public function __construct($labelsArr, $rowsArr, $scoredRowsCount=0) {
    $this->labelsArr=$labelsArr;
    $this->rowsArr=$rowsArr;
    $this->scoredRowsCount=$scoredRowsCount;
  }

  /**
   * Funkce pro sloučení další tabulky s výsledky do stávající
   * @param ScoringConfusionMatrix $confusionMatrix2
   */
  public function mergeScoringConfusionMatrix(ScoringConfusionMatrix $confusionMatrix2) {
    //FIXME implement
  }

  /**
   * Funkce pro vysčítání hodnot v tabulce
   *
*@param bool $nullValuesIncluded=true
   * @return ScoringResult
   */
  public function getScoringResult($nullValuesIncluded=true) {
    $truePositive=0;
    $falsePositive=0;
    $trueNegative=0;
    $falseNegative=0;
    /** @var int[] $ignoredLabelsPositions - pole pro určení těch pozic v tabulce, které mají být ignorovány */
    $ignoredLabelsPositions=[];
    if (!empty($this->labelsArr) && !empty($this->rowsArr)){
      //určení ignorování konkrétních pozic v tabulce
      if($nullValuesIncluded){
        for ($labelI=0;$labelI<count($this->labelsArr); $labelI++){
          if ($this->labelsArr[$labelI]=='null'||$this->labelsArr[$labelI]==''){
            $ignoredLabelsPositions[]=$labelI;
          }
        }
      }
      //spočítání hodnot v jednotlivých řádcích
      foreach ($this->rowsArr as $rowI=>$rowData){
        $nullRow=(in_array($rowI,$ignoredLabelsPositions));
        if ($nullRow){
          //jde o null řádek
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
          //nejde o null řádek
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