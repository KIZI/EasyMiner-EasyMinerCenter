<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use Nette\Utils\Json;

/**
 * Class TaskSettingsJson - class for work with task settings in format JSON
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class TaskSettingsJson {
  const PART_ANTECEDENT='antecedent';
  const PART_SUCCEDENT='succedent';
  const THRESHOLD_TYPE_ABS='Abs';
  const THRESHOLD_TYPE_PERCENTS='% of all';
  const COMPARE_GTE='Greater than or equal';
  const COMPARE_LTE='Less than or equal';
  /** @var array $data */
  private $data;

  #region global method to work with data
  /**
   * @param null|string $configJson
   * @throws \Nette\Utils\JsonException
   */
  public function __construct($configJson=null){
    if (is_string($configJson)){
      $this->data=Json::decode($configJson,Json::FORCE_ARRAY);
    }else{
      $this->data=['rule0'=>[]];
    }
  }

  /**
   * Method returning config data encoded as JSON
   * @return string
   * @throws \Nette\Utils\JsonException
   */
  public function getJsonString(){
    return Json::encode($this->getJsonData());
  }

  /**
   * Method returning config data
   * @return array
   */
  public function getJsonData(){
    return $this->data;
  }
  #endregion global method to work with data


  #region methods for work with attributes
  /**
   * Method returning names of attributes used in the rule pattern
   * @return string[]
   */
  public function getAttributeNames() {
    $result=$this->getAttributeNamesInConfigArr($this->data['rule0'][self::PART_ANTECEDENT]);
    $result2=$this->getAttributeNamesInConfigArr($this->data['rule0'][self::PART_SUCCEDENT]);
    if (!empty($result2)){
      foreach($result2 as $resultItem){
        $result[$resultItem]=$resultItem;
      }
    }
    return $result;
  }

  /**
   * @param $configArr
   * @return string[]
   */
  private function getAttributeNamesInConfigArr($configArr) {
    $result=[];
    if (@$configArr['type']=='cedent'){
      if (!empty($configArr['children'])){
        foreach($configArr['children'] as $childConfigArr){
          $childNamesArr=$this->getAttributeNamesInConfigArr($childConfigArr);
          if (!empty($childNamesArr))
            foreach($childNamesArr as $name){
              $result[$name]=$name;
            }
        }
      }
    }elseif(!empty($configArr['ref'])){
      $result[$configArr['ref']]=$configArr['ref'];
    }
    return $result;
  }
  #endregion methods for work with attributes

  #region methods for work with interest measures
  /**
   * @return array
   */
  public function getIMs() {
    return $this->data['rule0']['IMs'];
  }

  /**
   * @param array $IMs
   */
  public function setIMs($IMs) {
    $this->data['rule0']['IMs']=$IMs;
  }

  /**
   * Method returning array with names of used interest measures
   * @return string[]
   */
  public function getIMNames(){
    $IMs=$this->getIMs();
    $result=[];
    if (!empty($IMs)){
      foreach($IMs as $IM){
        $result[]=$IM['name'];
      }
    }
    return $result;
  }

  /**
   * Method for simple adding of an interest measure to task settings
   * @param string $imName
   * @param string $thresholdType
   * @param string $compareType
   * @param float $threshold
   */
  public function simpleAddIM($imName,$thresholdType,$compareType,$threshold) {
    $IMs=$this->getIMs();
    $IMs[]=[
      'name'=>$imName,
      'localizedName'=>$imName,
      'thresholdType'=>$thresholdType,
      'compareType'=>$compareType,
      'fields'=>[
        [
          'name'=>'threshold',
          'value'=>$threshold
        ]
      ],
      'threshold'=>$threshold,
      'alpha'=>null
    ];
    $this->setIMs($IMs);
  }
  #endregion methods for work with interest measures
}