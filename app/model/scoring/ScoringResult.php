<?php

namespace EasyMinerCenter\Model\Scoring;

/**
 * Class ScoringResult - třída pro záznam výsledků testování rulesetu či úlohy
 * @package EasyMinerCenter\Model\Scoring
 */
class ScoringResult {

  public $truePositive=0;
  public $falsePositive=0;
  public $rowsCount=0;

  /**
   * Funkce vracející data výsledků v podobě pole
   * @return array
   */
  public function getDataArr() {
    return ['truePositive'=>$this->truePositive,'falsePositive'=>$this->falsePositive,'rowsCount'=>$this->rowsCount];
  }

  /**
   * Funkce pro sloučení dvou částečných výsledků do jednoho
   * @param ScoringResult[] $scoringResults
   * @return ScoringResult
   */
  public static function merge($scoringResults){
    $result=new ScoringResult();
    if (!empty($scoringResults)){
      foreach($scoringResults as $scoringResult){
        $result->truePositive+=$scoringResult->truePositive;
        $result->falsePositive+=$scoringResult->falsePositive;
        $result->rowsCount+=$scoringResult->rowsCount;
      }
    }
    return $result;
  }
}