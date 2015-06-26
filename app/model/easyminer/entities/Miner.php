<?php

namespace App\Model\EasyMiner\Entities;

use LeanMapper\Entity;
use Nette;
use Nette\Utils\Json;

/**
 * Class Miner
 * @package App\Model\EasyMiner\Entities
 *
 * @property int|null $minerId = null
 * @property User|null $user = null m:hasOne
 * @property string $name = ''
 * @property string $type m:Enum('lm','r')
 * @property Datasource|null $datasource m:hasOne(datasource_id:) - zdroj původních dat v DB
 * @property Metasource|null $metasource m:hasOne(metasource_id:) - zdroj předzpracovaných dat v DB
 * @property RuleSet|null $ruleSet m:hasOne(rule_set_id:)
 * @property-read string $attributesTable
 * @property Nette\Utils\DateTime|null $created = null
 * @property Nette\Utils\DateTime|null $lastOpened = null
 * @property string $config
 * @property-read Task[] $tasks m:belongsToMany
 */
class Miner extends Entity{
  const TYPE_LM='lm';
  const TYPE_R='r';
  const DEFAULT_TYPE='r';

  /**
   * Funkce vracející přehled jednotlivých podporovaných typů minerů
   * @return array
   */
  public static function getTypes(){
    return array(
      self::TYPE_LM=>'LISp-Miner',
      self::TYPE_R=>'R',
    );
  }

  /**
   * Funkce vracející název tabulky s atributy
   * @return string
   */
  public function getAttributesTableName() {
    return 'ATR' . $this->minerId . '_' . $this->datasource->dbTable;
  }

  /**
   * Funkce vracející název tabulky s atributy
   * @return string
   */
  public function getRulesTableName(){
    return 'RULES'.$this->minerId.'_'.$this->datasource->dbTable;
  }

  /**
   * Funkce vracející název tabulky s atributy
   * @return string
   */
  public function getBBATableName(){
    return 'BBA'.$this->minerId.'_'.$this->datasource->dbTable;
  }

  /**
   * @return array
   */
  public function getConfig(){
    try{
      $arr=Nette\Utils\Json::decode($this->row->config,Json::FORCE_ARRAY);
    }catch (\Exception $e){
      $arr=array();
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
}