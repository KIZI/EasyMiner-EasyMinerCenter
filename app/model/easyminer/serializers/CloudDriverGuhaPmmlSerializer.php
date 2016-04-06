<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

/**
 * Class CloudDriverGuhaPmmlSerializer - serializer umožňující sestavit GUHA PMML dokument pro zadání úlohy pro cloud driver
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
class CloudDriverGuhaPmmlSerializer extends GuhaPmmlSerializer{

  /**
   * Funkce pro připojení informace o datasetu
   * @param \SimpleXMLElement|null $datasetExtension
   */
  protected function appendDatasetInfo(\SimpleXMLElement $datasetExtension){
    if (!empty($datasetExtension)){
      $datasetExtension['value']=$this->miner->metasource->ppDatasetId;
    }else{
      $this->addExtensionElement($header,'dataset',$this->miner->metasource->ppDatasetId);
    }
  }

  /**
   * Funkce pro připojení informací o nastavení úlohy
   */
  public function appendTaskSettings(){
    $taskSettingsSerializer=new CloudDriverTaskSettingsSerializer($this->pmml,$this->miner->type);
    $taskSettingsSerializer->task=$this->task;
    $this->pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
  }
}