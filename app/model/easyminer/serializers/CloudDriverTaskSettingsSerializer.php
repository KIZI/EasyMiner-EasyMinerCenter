<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\EasyMiner\Entities\Task;

/**
 * Class CloudDriverTaskSettingsSerializer - modified TaskSettingsSerializer for sending task settings to cloud mining driver
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CloudDriverTaskSettingsSerializer extends TaskSettingsSerializer{
  /** @var  Task $task */
  public $task;
  /** @var array|null $attributeRefPpAttributeIdsByName */
  private $attributeRefPpAttributeIdsByName=null;
  /** @var array|null $attributeRefPpAttributeIdsById */
  private $attributeRefPpAttributeIdsById=null;

  /**
   * Method for preparing of arrays for fingind IDs of attributes in preprocessing service
   */
  private function prepareAttributeRefIdsArr(){
    $attributes=$this->task->miner->metasource->attributes;
    if (!empty($attributes)){
      foreach($attributes as $attribute){
        if ($attribute->active){
          $this->attributeRefPpAttributeIdsByName[$attribute->name]=$attribute->ppDatasetAttributeId;
          $this->attributeRefPpAttributeIdsById[$attribute->attributeId]=$attribute->ppDatasetAttributeId;
        }
      }
    }
  }

  /**
   * Method for serialization info about relation between BBA anc concrete attribute
   * @param $attribute
   * @return int
   * @throws \Exception
   */
  protected function createBbaSetting($attribute) {
    $bbaId = $this->generateId();
    $bbaSettingXml = $this->bbaSettings->addChild("BBASetting");
    $bbaSettingXml->addAttribute('id', $bbaId);
    $textNode=$bbaSettingXml->addChild("Text");
    $textNode[0]=$attribute->name;
    $nameNode=$bbaSettingXml->addChild("Name");
    $nameNode[0]=$attribute->name;

    if (!empty($attribute->ppAttributeId) && $attribute->PpAttributeId>0){
      $ppDatasetAttributeId=$attribute->ppAttributeId;
    }else{
      //we have to get ID of attribute from metasource
      if ($this->attributeRefPpAttributeIdsByName==null){
        $this->prepareAttributeRefIdsArr();
      }
      if (!empty($attribute->attributeId)&&!empty($this->attributeRefPpAttributeIdsById[$attribute->attributeId])){
        $ppDatasetAttributeId=$this->attributeRefPpAttributeIdsById[$attribute->attributeId];
      }elseif (!empty($this->attributeRefPpAttributeIdsByName[$attribute->name])){
        $ppDatasetAttributeId=$this->attributeRefPpAttributeIdsByName[$attribute->name];
      }else{
        throw  new \Exception('Requested attribute is not available!');
      }
    }
    $bbaSettingXml->addChild("FieldRef", $ppDatasetAttributeId);
    $this->createCoefficient($bbaSettingXml,$attribute);
    return $bbaId;
  }


}