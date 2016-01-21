<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use Nette\Utils\Json;

/**
 * Class TaskSettingsJson - třída pro práci s nastavením úlohy ve formátu JSON
 *
*@package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
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

  #region globální funkce pro přístup k datům
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
   * Funkce vracející data zakódovaná jako Json
   * @return string
   * @throws \Nette\Utils\JsonException
   */
  public function getJsonString(){
    return Json::encode($this->getJsonData());
  }

  /**
   * Funkce vracející data zakódovaná jako Json
   * @return array
   */
  public function getJsonData(){
    return $this->data;
  }
  #endregion globální funkce pro přístup k datům


  #region funkce pro práci s atributy
  /**
   * Funkce vracející jména měr atributů použitých ve vzoru pravidla
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
  #endregion funkce pro práci s atributy

  #region práce s měrami zajímavosti
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
   * Funkce vracející pole se jmény použitých měr zajímavosti
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
   * Funkce pro jednoduché přidání míry zajímavosti do nastavení úlohy
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
  #endregion práce s měrami zajímavosti
}