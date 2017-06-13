<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\EasyMiner\Authorizators\IOwnerResource;
use LeanMapper\Entity;
use Nette;
use Nette\Utils\Json;

/**
 * Class Miner
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $minerId = null
 * @property User|null $user = null m:hasOne
 * @property string $name = ''
 * @property string $type m:Enum('lm','r','cloud')
 * @property Datasource|null $datasource m:hasOne(datasource_id:) - zdroj původních dat v DB
 * @property Metasource|null $metasource m:hasOne(metasource_id:) - zdroj předzpracovaných dat v DB
 * @property RuleSet|null $ruleSet m:hasOne(rule_set_id:)
 * @property-read string $attributesTable
 * @property \DateTime|null $created = null
 * @property \DateTime|null $lastOpened = null
 * @property string $config
 * @property-read Task[] $tasks m:belongsToMany
 * @property-read OutliersTask[] $outliersTasks m:belongsToMany
 * @property-read string $typeName
 */
class Miner extends Entity implements IOwnerResource{
  const TYPE_LM     = 'lm';
  const TYPE_LM_NAME= 'LISp-Miner';
  const TYPE_R      = 'r';
  const TYPE_R_NAME = 'R';
  const TYPE_CLOUD  = 'cloud';
  const TYPE_CLOUD_NAME='Cloud';
  public static $dbTypeMiners=[
    DbConnection::TYPE_MYSQL=>[self::TYPE_R, self::TYPE_LM],
    DbConnection::TYPE_LIMITED=>[self::TYPE_CLOUD],
    DbConnection::TYPE_UNLIMITED=>[self::TYPE_CLOUD]
  ];

  /**
   * Method returning the name of a type of miner
   * @return string
   */
  public function getTypeName(){
    switch($this->type){
      case self::TYPE_LM: return self::TYPE_LM_NAME;
      case self::TYPE_R: return self::TYPE_R_NAME;
      case self::TYPE_CLOUD: return self::TYPE_CLOUD_NAME;
      default: return '';
    }
  }

  /**
   * Method returning a list of supported types of miners
   * @param string $dbType
   * @return array
   */
  public static function getTypes($dbType=null){
    $types=$dbType? @self::$dbTypeMiners[$dbType]: [];
    $reflectionClass=new \ReflectionClass(__CLASS__);
    $constants=$reflectionClass->getConstants();
    $result=[];
    if (!empty($constants)){
      foreach($constants as $constantName=>$constantValue){
        if (preg_match('/TYPE_(.*)+_NAME/',$constantName)){
          $name=substr($constantName,0,strlen($constantName)-5);
          if (!empty($dbType) && !in_array($constants[$name],$types)){continue;}
          $result[$constants[$name]]=$constantValue;
        }
      }
    }
    return $result;
  }

  /**
   * Method returning the name of table with attributes
   * @return string
   * XXX remove from LM driver
   */
  public function getAttributesTableName() {
    return 'ATR' . $this->minerId . '_' . $this->datasource->name;
  }

  /**
   * @return array
   */
  public function getConfig(){
    try{
      $arr=Nette\Utils\Json::decode($this->row->config,Json::FORCE_ARRAY);
    }catch (\Exception $e){
      $arr=[];
    }
    return $arr;
  }

  /**
   * @param array $config
   * @throws Nette\Utils\JsonException
   */
  public function setConfig($config){
    if (is_array($config)||is_object($config)){
      $this->row->config=Json::encode($config);
    }
  }

  /**
   * Method returning the external configuration
   * @return array
   */
  public function getExternalConfig(){
    $config=$this->getConfig();
    if (!empty($config['ext'])){
      return $config['ext'];
    }else{
      return [];
    }
  }

  /**
   * Method for setting of external configuration
   * @param array $externalConfig
   */
  public function setExternalConfig($externalConfig){
    $config=$this->getConfig();
    $config['ext']=$externalConfig;
    $this->setConfig($config);
  }

  /**
   * Method returning an array with basic data properties
   * Funkce vracející data mineru v podobě pole
   * @return array
   */
  public function getDataArr(){
    $rowData=$this->getRowData();
    return [
      'id'=>$this->minerId,
      'name'=>$this->name,
      'type'=>$this->type,
      'datasourceId'=>$rowData['datasource_id'],
      'metasourceId'=>$rowData['metasource_id'],
      'ruleSetId'=>$rowData['rule_set_id'],
      'config'=>$this->getConfig(),
      'created'=>!empty($this->created)?$this->created->format('c'):null,
      'lastOpened'=>!empty($this->lastOpened)?$this->lastOpened->format('c'):null
    ];
  }

  /**
   * Method returning the ID of owner (User)
   * @return int
   */
  function getUserId() {
    if (!empty($this->user)){
      return $this->user->userId;
    }else{
      return null;
    }
  }

  /**
   * Method returning a string identifier of the Resource.
   * @return string
   */
  function getResourceId() {
    return 'ENTITY:Miner';
  }
}