<?php

namespace EasyMinerCenter\Model\Scoring;

/**
 * Class ScoringResult - class for representation of test results of a ruleset or a task
 * @package EasyMinerCenter\Model\Scoring
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ScoringResult {

  public $truePositive=0;
  public $falsePositive=0;
  public $rowsCount=0;
  public $trueNegative=null;
  public $falseNegative=null;

  /**
   * ScoringResult constructor.
   * @param int $truePositive = 0
   * @param int $falsePositive = 0
   * @param int $rowsCount = 0
   * @param int $trueNegative = null
   * @param int $falseNegative = null
   */
  public function __construct($truePositive=0, $falsePositive=0, $trueNegative=null, $falseNegative=null, $rowsCount=0) {
    $this->truePositive=$truePositive;
    $this->falsePositive=$falsePositive;
    $this->trueNegative=$trueNegative;
    $this->falseNegative=$falseNegative;
    $this->rowsCount=$rowsCount;
  }

  /**
   * Method returning result data in form of array
   * @return array
   */
  public function getDataArr() {
    $result=['truePositive'=>$this->truePositive,'falsePositive'=>$this->falsePositive,'rowsCount'=>$this->rowsCount];
    if ($this->trueNegative!==null || $this->falseNegative!==null){
      $result['trueNegative']=intval($this->trueNegative);
      $result['falseNegative']=intval($this->falseNegative);
    }
    return $result;
  }

  /**
   * Method returning result data in form of array
   * @return array
   */
  public function getCorrectIncorrectDataArr() {
    $result=['correct'=>$this->truePositive,'incorrect'=>$this->falsePositive,'unclassified'=>$this->rowsCount-$this->truePositive-$this->falsePositive,'rowCount'=>$this->rowsCount];
    return $result;
  }

  /**
   * Method for merging of two ScoringResults to one...
   * @param ScoringResult[] $scoringResults
   * @return ScoringResult
   */
  public static function merge($scoringResults){
    $result=new ScoringResult();
    $falseValues=false;
    if (!empty($scoringResults)){
      foreach($scoringResults as $scoringResult){
        $result->truePositive+=$scoringResult->truePositive;
        $result->falsePositive+=$scoringResult->falsePositive;
        $result->rowsCount+=$scoringResult->rowsCount;
        if (!$falseValues && ($scoringResult->falseNegative!==null || $scoringResult->trueNegative!==null)){
          $falseValues=true;
        }
        if ($falseValues){
          $result->falseNegative+=$scoringResult->falseNegative;
          $result->trueNegative+=$scoringResult->trueNegative;
        }
      }
      if (!$falseValues){
        $result->falseNegative=null;
        $result->trueNegative=null;
      }
    }
    return $result;
  }
}