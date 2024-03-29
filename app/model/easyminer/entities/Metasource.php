<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use LeanMapper\Entity;

/**
 * Class Metasource
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $metasourceId = null
 * @property User|null $user = null m:hasOne
 * @property string $type = m:Enum('mysql','limited','unlimited')
 * @property int|null $ppDatasetId = null
 * @property string $state = m:Enum('available','unavailable','unlimited')
 * @property Datasource|null $datasource = null m:hasOne
 * @property string $dbServer = ''
 * @property string|null $dbApi = null
 * @property int|null $dbPort = null
 * @property string $dbUsername = ''
 * @property string $dbName = ''
 * @property string $name = ''
 * @property int|null $size = null
 * @property-read MetasourceTask[] $metasourceTasks m:belongsToMany
 * @property-read Attribute[] $attributes m:belongsToMany
 * @property-read PpConnection $ppConnection
 * @property-read string $dbTable
 */
class Metasource extends Entity{

  const STATE_AVAILABLE='available';
  const STATE_UNAVAILABLE='unavailable';
  const STATE_PREPARATION='preparation';

  /**
   * Method returning an array with basic data properties of the given datasource
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->metasourceId,
      'type'=>$this->type,
      'name'=>$this->name,
      'ppDatasetId'=>$this->ppDatasetId,
      'state'=>$this->state,
      'size'=>$this->size
    ];
  }

  /**
   * Method for creation of new Metasource using a PpConnection
   * @param PpConnection $ppConnection
   * @return Metasource
   */
  public static function newFromPpConnection(PpConnection $ppConnection) {
    $metasource = new Metasource();
    $metasource->type=$ppConnection->type;
    $metasource->dbName=$ppConnection->dbName;
    $metasource->dbServer=$ppConnection->dbServer;
    $metasource->dbApi = $ppConnection->dbApi;
    if (!empty($ppConnection->dbPort)){
      $metasource->dbPort=$ppConnection->dbPort;
    }
    $metasource->dbUsername=$ppConnection->dbUsername;
    $metasource->setDbPassword($ppConnection->dbPassword);
    return $metasource;
  }

  /**
   * Method returning PpConnection (instructions for connection to Data/Preprocessing service)
   * @return PpConnection
   */
  public function getPpConnection(){
    $ppConnection=new PpConnection();
    $ppConnection->dbName=$this->dbName;
    $ppConnection->dbUsername=$this->dbUsername;
    $ppConnection->dbPassword=$this->getDbPassword();
    $ppConnection->dbPort=$this->dbPort;
    $ppConnection->dbApi=$this->dbApi;
    $ppConnection->dbServer=$this->dbServer;
    $ppConnection->type=$this->type;
    return $ppConnection;
  }

  /**
   * @return string
   */
  public function getDbPassword(){
    /** @noinspection PhpUndefinedFieldInspection */
    if (!empty($this->row->db_password)) {
      /** @noinspection PhpUndefinedFieldInspection */
      return StringsHelper::decodePassword($this->row->db_password);
    }else{
      return null;
    }
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    /** @noinspection PhpUndefinedFieldInspection */
    $this->row->db_password=StringsHelper::encodePassword($password);
  }

  /**
   * Method returning array of Attributes belonging to this Metasource; keys = IDs ot Attributes
   * @return Attribute[]
   */
  public function getAttributesArr(){
    $attributesArr=[];
    if (!empty($this->attributes)){
      foreach($this->attributes as $attribute){
        $attributesArr[$attribute->attributeId]=$attribute;
      }
    }
    return $attributesArr;
  }

  /**
   * Method returning array of Attributes belonging to this Metasource; keys = names of Attributes
   * @return Attribute[]
   */
  public function getAttributesByNamesArr(){
    $attributesArr=[];
    if (!empty($this->attributes)){
      foreach($this->attributes as $attribute){
        $attributesArr[$attribute->name]=$attribute;
      }
    }
    return $attributesArr;
  }

  /**
   * Method returning Attribute by the given name
   * @param string $name
   * @return Attribute|null
   */
  public function getAttributeByName($name){
    $attributes=$this->attributes;
    if (!empty($attributes)){
      foreach ($attributes as $attribute){
        if ($attribute->name==$name){
          return $attribute;
        }
      }
    }
    return null;
  }

  /**
   * @return string
   */
  public function getDbTable() {
    /** @noinspection PhpUndefinedFieldInspection */
    return @$this->row->name;
  }
} 