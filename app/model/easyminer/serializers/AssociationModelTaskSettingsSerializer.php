<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;
use Nette\Utils\Json;

/**
 * Class AssociationModelTaskSettingsSerializer - třída pro serializaci zadání úlohy v jednoduchém formátu pro PMML AssociationModel
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 */
class AssociationModelTaskSettingsSerializer{
  /** @var  \SimpleXMLElement $pmml */
  private $pmml;

  #region construct, (get|set)Pmml
  /**
   * AssociationModelTaskSettingsSerializer constructor.
   * @param \SimpleXMLElement|string $pmml
   */
  public function __construct($pmml = null){
    if (!empty($pmml)){
      $this->setPmml($pmml);
    }
  }

  /**
   * @param \SimpleXMLElement|string $pmml
   */
  public function setPmml($pmml){
    if ($pmml instanceof \SimpleXMLElement){
      $this->pmml=$pmml;
    }else{
      $this->pmml=simplexml_load_string($pmml);
    }
  }

  /**
   * Funkce vracející SimpleXML s PMML...
   * @return \SimpleXMLElement
   */
  public function getPmml(){
    return $this->pmml;
  }
  #endregion

  public function settingsFromJson($json){
    if (is_string($json)){
      $json=Json::decode($json,Json::FORCE_ARRAY);
    }
    /** @var \SimpleXMLElement $associationModelXml */
    /** @noinspection PhpUndefinedFieldInspection */
    $associationModelXml=$this->pmml->AssociationModel;

    /** @var \SimpleXMLElement $taskSettingXml */
    $taskSettingXml=$associationModelXml->addChild('Extension');
    $taskSettingXml->addAttribute('name','TaskSetting');
    $antecedentSettingXml=$taskSettingXml->addChild('AntecedentSetting');
    $consequentSettingXml=$taskSettingXml->addChild('ConsequentSetting');
    $interestMeasureSettingXml=$taskSettingXml->addChild('InterestMeasureSetting');
    $specialInterestMeasureSettingXml=$taskSettingXml->addChild('SpecialInterestMeasureSetting');
    $taskSettingXml->addChild('LimitHits',$json['limitHits']);

    $rule0=$json['rule0'];
    $IMs=$rule0['IMs'];

    $this->serializeCedentSetting($rule0[TaskSettingsJson::PART_ANTECEDENT],$antecedentSettingXml);
    $this->serializeCedentSetting($rule0[TaskSettingsJson::PART_SUCCEDENT],$consequentSettingXml);

    //region IMs
    $associationModelXml['minimumConfidence']=0;
    $associationModelXml['minimumSupport']=0;
    if (!empty($IMs)){
      foreach($IMs as $IM){
        $interestMeasureXml=$interestMeasureSettingXml->addChild('InterestMeasure');
        $interestMeasureXml->addAttribute('name',$IM['name']);
        $interestMeasureXml->addAttribute('threshold',@$IM['threshold']);
        $interestMeasureXml->addAttribute('thresholdType',@$IM['thresholdType']);
        $interestMeasureXml->addAttribute('compareType',@$IM['compareType']);

        if ($IM['name']=='CONF' && $IM['compareType']==TaskSettingsJson::COMPARE_GTE){
          if ($IM['thresholdType']==TaskSettingsJson::THRESHOLD_TYPE_ABS){
            $associationModelXml['minimumConfidence']=$IM['threshold']/$associationModelXml['numberOfTransactions'];
          }else{
            $associationModelXml['minimumConfidence']=$IM['threshold'];
          }
        }
        if ($IM['name']=='SUPP' && $IM['compareType']==TaskSettingsJson::COMPARE_GTE){
          if ($IM['thresholdType']==TaskSettingsJson::THRESHOLD_TYPE_ABS){
            $associationModelXml['minimumSupport']=$IM['threshold']/$associationModelXml['numberOfTransactions'];
          }else{
            $associationModelXml['minimumSupport']=$IM['threshold'];
          }
        }
      }
    }
    $specialIMs=$rule0['specialIMs'];
    if (!empty($specialIMs)){
      foreach($specialIMs as $specialIM){
        $specialInterestMeasureXml=$specialInterestMeasureSettingXml->addChild('InterestMeasure');
        $specialInterestMeasureXml->addAttribute('name',$specialIM['name']);
      }
    }
    //endregion IMs
  }

  /**
   * @param array $cedentSettingsJson
   * @param \SimpleXMLElement $configXml
   */
  private function serializeCedentSetting($cedentSettingsJson,\SimpleXMLElement &$configXml){
    if (count($cedentSettingsJson['children'])>0){
      foreach($cedentSettingsJson['children'] as $cedentChild){
        $itemXml=$configXml->addChild('Item');
        $itemXml->addAttribute('field',$cedentChild['name']);

        if ($cedentChild['category']=='One category'){
          foreach($cedentChild['fields'] as $field){
            if ($field['name']=='category'){
              $itemXml->addAttribute('category',@$field['value']);
            }
          }
        }
      }
    }
  }
}