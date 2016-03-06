<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use LeanMapper\Entity;

/**
 * Class Metasource
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $metasourceId = null
 * @property User|null $user = null m:hasOne
 * @property string $type = m:Enum('mysql','cassandra')
 * @property string $dbServer
 * @property int|null $dbPort = null
 * @property string $dbUsername
 * @property string $dbName
 * @property string $attributesTable
 * @property-read Task[] $tasks m:belongsToMany
 * @property-read Attribute[] $attributes m:belongsToMany
 * @property-read DbConnection $dbConnection
 */
class Metasource extends Entity{
  /**
   * Funkce vracející přehled typů databází
   * @return array
   */
  public static function getTypes(){
    return array(
      'mysql'=>'MySQL',
      'cassandra'=>'Cassandra DB'
    );
  }

  /**
   * @return DbConnection
   */
  public function getDbConnection(){
    $dbConnection=new DbConnection();
    $dbConnection->dbName=$this->dbName;
    $dbConnection->dbUsername=$this->dbUsername;
    $dbConnection->dbPassword=$this->getDbPassword();
    $dbConnection->dbPort=$this->dbPort;
    $dbConnection->dbServer=$this->dbServer;
    $dbConnection->type=$this->type;
    return $dbConnection;
  }

  /**
   * @return string
   */
  public function getDbPassword(){
    if (empty($this->row->db_password)){return null;}
    return StringsHelper::decodePassword($this->row->db_password);
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    $this->row->db_password=StringsHelper::encodePassword($password);
  }

  /**
   * Funkce vracející pole atributů zahrnutých v rámci tohoto Metasource
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
   * Funkce vracející pole atributů zahrnutých v rámci tohoto Metasource
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
   * Funkce vracející atribut dle zadaného jména
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
} 