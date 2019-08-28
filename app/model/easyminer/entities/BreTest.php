<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use Nette\Utils\Json;

/**
 * Class BreTestCase
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $breTestId
 * @property string $name = ''
 * @property string $infoText = ''
 * @property User $user m:hasOne
 * @property Miner $miner m:hasOne
 * @property RuleSet $ruleSet m:hasOne
 * @property Datasource|null $datasource m:hasOne
 * @property string $testKey = ''
 * @property string $allowedEditorOperators = ''
 * @property string $allowedRuleAttributesCount = ''
 * @property BreTestUser[] $breTestUsers m:belongsToMany
 * @property-read BreTestUserLog[] $breTestUserLogs m:belongsToMany
 */
class BreTest extends Entity {

  /**
   * @return int
   */
  public function getBreTestUsersCount(){
    return count($this->breTestUsers);
  }

  /**
   * @return array
   */
  public function getAllowedEditorOperators(){
    try{
      $arr=Json::decode($this->row->allowed_editor_operators,Json::FORCE_ARRAY);
    }catch (\Exception $e){
      $arr=[];
    }
    if (empty($arr)){
      $arr=['is'];
    }
    return $arr;
  }

  /**
   * @param array $config
   * @throws \Nette\Utils\JsonException
   */
  public function setAllowedEditorOperators($config){
    if (is_array($config)||is_object($config)){
      $this->row->allowed_editor_operators=Json::encode($config);
    }
  }

  /**
   * @return array
   */
  public function getAllowedRuleAttributesCount(){
    try{
      $arr=Json::decode($this->row->allowed_rule_attributes_count,Json::FORCE_ARRAY);
    }catch (\Exception $e){
      $arr=['antecedentMin'=>null,'antecedentMax'=>null,'consequentMin'=>null,'consequentMax'=>null];
    }
    return $arr;
  }

  /**
   * @param array $config
   * @throws \Nette\Utils\JsonException
   */
  public function setAllowedRuleAttributesCount($config){
    if (is_array($config)||is_object($config)){
      $this->row->allowed_rule_attributes_count=Json::encode($config);
    }
  }
}