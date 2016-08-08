<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;

/**
 * Trait PmmlSerializerTrait obsahuje metody sdílené všemi serializacemi do PMML modelů
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
trait PmmlSerializerTrait{

  /**
   * Funkce pro přidání tagu <Extension name="..." value="..." />
   *
   * @param \SimpleXMLElement &$parentSimpleXmlElement
   * @param string $extensionName
   * @param string $extensionValue
   * @param string|null $extensionExtender
   */
  protected function addExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender=null, $groupExtensions=true){
    if ($groupExtensions && count($parentSimpleXmlElement->Extension)>0){//TODO tohle nefunguje v rámci nového serveru...
      $siblinkElement = $parentSimpleXmlElement->Extension[0];
      $siblinkElementDom=dom_import_simplexml($siblinkElement);
      //připravení elementu pro připojení
      $extensionElement=new \SimpleXMLElement('<Extension />');
      $extensionElement->addAttribute('name',$extensionName);
      $extensionElement->addAttribute('value',$extensionValue);
      if ($extensionExtender!==null){
        $extensionElement->addAttribute('extender',$extensionExtender);
      }
      $extensionElementDom = $siblinkElementDom->ownerDocument->importNode(dom_import_simplexml($extensionElement), true);
      $siblinkElementDom->parentNode->insertBefore($extensionElementDom, $siblinkElementDom);
    }else{
      $extensionElement=$parentSimpleXmlElement->addChild('Extension');
      $extensionElement->addAttribute('name',$extensionName);
      $extensionElement->addAttribute('value',$extensionValue);
      if ($extensionExtender!==null){
        $extensionElement->addAttribute('extender',$extensionExtender);
      }
    }
  }

  /**
   * @param \SimpleXMLElement $parentSimpleXmlElement
   * @param string $extensionName
   * @param string $extensionValue
   * @param string|null $extensionExtender = null
   * @param bool $groupExtensions = true
   */
  protected function setExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender=null, $groupExtensions=true){
    if ($extensionElement=$this->getExtensionElement($parentSimpleXmlElement,$extensionName)){
      //existuje příslušný element Extension
      $extensionElement['name']=$extensionName;
      $extensionElement['value']=$extensionValue;
      if ($extensionExtender!=''){
        $extensionElement['extender']=$extensionExtender;
      }else{
        unset($extensionExtender['extender']);
      }
    }else{
      $this->addExtensionElement($parentSimpleXmlElement,$extensionName,$extensionValue,$extensionExtender,$groupExtensions);
    }
  }

  /**
   * Funkce vracející konkrétní extension
   * @param \SimpleXMLElement $parentSimpleXmlElement
   * @param string $extensionName
   * @return \SimpleXMLElement|null
   */
  protected function getExtensionElement(\SimpleXMLElement &$parentSimpleXmlElement, $extensionName){
    if (count($parentSimpleXmlElement->Extension)>0){
      foreach($parentSimpleXmlElement->Extension as $extension){
        if (@$extension['name']==$extensionName){
          return $extension;
        }
      }
    }
    return null;
  }

  /**
   * Funkce pro nastavení základních informací o úloze, ke které je vytvářena serializace
   */
  private function appendTaskInfo() {
    /** @var \SimpleXMLElement $headerXml */
    $headerXml=$this->pmml->Header;
    if ($this->task->type==Miner::TYPE_LM){
      //lispminer
      $this->setExtensionElement($headerXml,'subsystem','4ft-Miner');
      $this->setExtensionElement($headerXml,'module','LMConnect');
    }elseif($this->task->type==Miner::TYPE_R){
      //R
      $this->setExtensionElement($headerXml,'subsystem','R');
      $this->setExtensionElement($headerXml,'module','Apriori-R');
    }else{
      //other
      $this->setExtensionElement($headerXml,'subsystem',$this->task->type);
      $this->setExtensionElement($headerXml,'module',$this->task->type);
    }
    //základní informace o autorovi a timestamp
    $this->setExtensionElement($headerXml,'author',(!empty($this->miner->user)?$this->miner->user->name:''));
    $headerXml->Timestamp=date('Y-m-d H:i:s').' GMT '.str_replace(['+','-'],['+ ','- '],date('P'));
    $applicationXml=$headerXml->Application;
    $applicationXml['name']='EasyMiner';
    $applicationXml['version']=$this->appVersion;
  }
}