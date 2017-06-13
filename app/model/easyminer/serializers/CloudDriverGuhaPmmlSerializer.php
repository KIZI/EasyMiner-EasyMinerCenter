<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

/**
 * Class CloudDriverGuhaPmmlSerializer - serializer for GUHA PMML from cloud miner
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CloudDriverGuhaPmmlSerializer extends GuhaPmmlSerializer{

  /**
   * Method for appending info about dataset
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
   * Method for appending task settings
   */
  public function appendTaskSettings(){
    $taskSettingsSerializer=new CloudDriverTaskSettingsSerializer($this->pmml,$this->miner->type);
    $taskSettingsSerializer->task=$this->task;
    $this->pmml=$taskSettingsSerializer->settingsFromJson($this->task->taskSettingsJson);
  }
}