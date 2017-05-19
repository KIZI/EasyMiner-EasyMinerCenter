<?php

namespace EasyMinerCenter\Model\Scoring\EasyMinerScorer;
use EasyMinerCenter\Model\Scoring\ScoringConfusionMatrix;
use Nette\Utils\Json;

/**
 * Class EasyMinerScoringResult - class for representation of scoring request results using EasyMinerScorer
 * @package EasyMinerCenter\Model\Scoring\EasyMinerScorer
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class EasyMinerScoringResult {
  /** @var string[] $rules */
  private $rules=[];
  /** @var string $classificationAttribute */
  private $classificationAttribute='';
  /** @var string[] $classificationResults */
  private $classificationResults=[];
  /** @var  ScoringConfusionMatrix $confusionMatrix */
  private $confusionMatrix;

  /**
   * @param array|string|null $scorerResponseData - data from the Scorer service
   */
  public function __construct($scorerResponseData=null) {
    if (!empty($scorerResponseData)){
      $this->parseResultResponse($scorerResponseData);
    }
  }

  /**
   * Method for parsing of a response from the Scorer service
   * @param array|string $scorerResponseData
   */
  private function parseResultResponse($scorerResponseData){
    if (is_string($scorerResponseData)){
      $scorerResponseData=Json::decode($scorerResponseData);
    }
    #region score and rule
    if (!empty($scorerResponseData['score'])){
      foreach ($scorerResponseData['score'] as $i=>$rowResult){
        if ($rowResult==null){
          $this->classificationResults[]=null;
          $this->rules[]=null;
          continue;
        }
        if ($this->classificationAttribute==""){
          foreach($rowResult as $key=>$value){
            $this->classificationAttribute=$key;
            break;
          }
        }
        $this->classificationResults[]=@$rowResult[$this->classificationAttribute];
        if (!empty($scorerResponseData['rule'][$i])&&!empty($scorerResponseData['rule'][$i]['id'])){
          $this->rules[]=$scorerResponseData['rule'][$i]['id'];
        }else{
          $this->rules[]=null;
        }
      }
    }
    #endregion score and rule
    #region confusionMatrix
    $this->confusionMatrix=new ScoringConfusionMatrix($scorerResponseData['confusionMatrix']['labels'],$scorerResponseData['confusionMatrix']['matrix']);
    #endregion confusionMatrix
  }

  #region getters
  /**
   * Method returning an array with IDs of used Rules
   * @return \string[]
   */
  public function getRules() {
    return $this->rules;
  }

  /**
   * @return string
   */
  public function getClassificationAttribute() {
    return $this->classificationAttribute;
  }

  /**
   * @return ScoringConfusionMatrix
   */
  public function getScoringConfusionMatrix() {
    return $this->confusionMatrix;
  }

  /**
   * @return string
   */
  public function getClassificationResults() {
    return $this->classificationResults;
  }
  #endregion getters


  /**
   * Method for adding of new results to the existing instance of EasyminerScoringResult
   * @param EasyMinerScoringResult $scoringResult2
   */
  public function mergeEasyMinerScoringResult(EasyMinerScoringResult $scoringResult2) {
    if ($this->classificationAttribute!=$scoringResult2->getClassificationAttribute()){
      if ($this->classificationAttribute=="" && $scoringResult2->classificationAttribute!=""){
        $this->classificationAttribute=$scoringResult2->classificationAttribute;
      }elseif($this->classificationAttribute!="" && $scoringResult2->classificationAttribute!=""){
        throw new \BadFunctionCallException('Classification attributes are different!');
      }
    }
    array_push($this->rules,$scoringResult2->getRules());
    $this->confusionMatrix->mergeScoringConfusionMatrix($scoringResult2->getScoringConfusionMatrix());
    array_push($this->classificationResults,$scoringResult2->getClassificationResults());
  }

}