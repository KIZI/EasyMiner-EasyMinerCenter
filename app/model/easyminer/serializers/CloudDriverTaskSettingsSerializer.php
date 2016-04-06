<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\EasyMiner\Entities\Task;

/**
 * Class CloudDriverTaskSettingsSerializer - upravený TaskSettingsSerializer pro odeslání zadání úlohy pro cloud mining driver
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
class CloudDriverTaskSettingsSerializer extends TaskSettingsSerializer{
  /** @var  Task $task */
  public $task;
  /** @var array|null $attributeRefPpAttributeIdsByName */
  private $attributeRefPpAttributeIdsByName=null;
  /** @var array|null $attributeRefPpAttributeIdsById */
  private $attributeRefPpAttributeIdsById=null;

  /**
   * Funkce pro přípravu polí pro dohledávání IDček atributů v rámci preprocessing služby
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
   * Funkce pro serializaci informace o vazbě BBA na konkrétní atribut
   * @param $attribute
   * @return int
   * @throws \Exception
   */
  protected function createBbaSetting($attribute) {
    $bbaId = $this->generateId();
    $bbaSettingXml = $this->bbaSettings->addChild("BBASetting");
    $bbaSettingXml->addAttribute('id', $bbaId);
    $bbaSettingXml->addChild("Text", $attribute->name);
    $bbaSettingXml->addChild("Name", $attribute->name);

    if (!empty($attribute->ppAttributeId) && $attribute->PpAttributeId>0){
      $ppDatasetAttributeId=$attribute->ppAttributeId;
    }else{
      //musíme zjistit ID atributu z metasource
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