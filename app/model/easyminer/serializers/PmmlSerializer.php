<?php

namespace App\Model\EasyMiner\Serializers;

use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;

/**
 * Class PmmlSerializer - serializer umožňující sestavit PMML dokument z dat zadané úlohy...
 * @package App\Model\EasyMiner\Serializers
 */
class PmmlSerializer {
  /** @var  \SimpleXMLElement $pmml */
  private $pmml;
  /** @var  Task $task */
  private $task;
  /** @var  Miner $miner */
  private $miner;

  /**
   * @return \SimpleXMLElement
   */
  public function getPmml(){
    return $this->pmml;
  }

  /**
   * @param Task $task
   * @param \SimpleXMLElement|string|null $pmml = null
   */
  public function __construct(Task $task, $pmml = null){
    if ($task instanceof Task){
      $this->task=$task;
      $this->miner=$task->miner;
    }
    if (!empty($pmml)){
      if ($pmml instanceof \SimpleXMLElement){
        $this->pmml=$pmml;
      }elseif(is_string($pmml)){
        $this->pmml=simplexml_load_string($pmml);
      }
    }
    if (!$pmml instanceof \SimpleXMLElement){
      $this->prepareBlankPmml();
    }
  }

  /**
   * Funkce připojující informace o připojení k databázi do PMML souboru
   */
  public function appendMetabaseInfo() {
    /** @var \SimpleXMLElement $headerXml */
    $headerXml=$this->pmml->Header;
    $dbConnection=$this->miner->metasource->getDbConnection();
    $this->addExtensionElement($headerXml,'database-type',$dbConnection->type);
    $this->addExtensionElement($headerXml,'database-server',$dbConnection->dbServer);
    $this->addExtensionElement($headerXml,'database-name',$dbConnection->dbName);
    $this->addExtensionElement($headerXml,'database-user',$dbConnection->dbUsername);
    $this->addExtensionElement($headerXml,'database-password',$dbConnection->dbPassword);
  }

  /**
   * Funkce připravující prázdný PMML dokument
   */
  private function prepareBlankPmml(){//TODO doplnění informací o
    $this->pmml = simplexml_load_string('<'.'?xml version="1.0" encoding="UTF-8"?>
      <'.'?oxygen SCHSchema="http://sewebar.vse.cz/schemas/GUHARestr0_1.sch"?>
      <PMML xmlns="http://www.dmg.org/PMML-4_0" version="4.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:pmml="http://www.dmg.org/PMML-4_0" xsi:schemaLocation="http://www.dmg.org/PMML-4_0 http://sewebar.vse.cz/schemas/PMML4.0+GUHA0.1.xsd">
        <Header copyright="Copyright (c) KIZI UEP">

          <Extension name="author" value="admin"/>
          <Extension name="subsystem" value="4ft-Miner"/>
          <Extension name="module" value="4ftResult.exe"/>
          <Extension name="format" value="4ftMiner.Task"/>
          <Application name="EasyMiner" version="2.0 '.date('Y').'"/>
          <Annotation/>
          <Timestamp>20.11.2014 17:01:45</Timestamp>
        </Header>
        <DataDictionary/>
        <TransformationDictionary/>
        <guha:AssociationModel xmlns:guha="http://keg.vse.cz/ns/GUHA0.1rev1" xmlns="" />
      </PMML>');
      //<Extension name="dataset" value="...."/>
      /** @var \SimpleXMLElement $extension */
      $header=$this->pmml->Header;
      $this->addExtensionElement($header,'dataset',$this->miner->metasource->attributesTable);
  }


  /**
   * Funkce pro přidání tagu <Extension name="..." value="..." />
   * @param \SimpleXMLElement &$simpleXmlElement
   * @param string $extensionName
   * @param string $extensionValue
   */
  private function addExtensionElement(&$simpleXmlElement,$extensionName,$extensionValue){
    $extensionElement=$simpleXmlElement->addChild('Extension');
    $extensionElement->addAttribute('name',$extensionName);
    $extensionElement->addAttribute('value',$extensionValue);
  }
}