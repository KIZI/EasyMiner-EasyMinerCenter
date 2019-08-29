<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;


use LeanMapper\Entity;

/**
 * Class Rule
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $ruleId
 * @property Task|null $task m:hasOne
 * @property-read RuleSetRuleRelation[] $ruleSetRuleRelations m:belongsToMany
 * @property string $text
 * @property string $pmmlRuleId = ''
 * @property Cedent|null $antecedent m:hasOne(antecedent)
 * @property Cedent|null $consequent m:hasOne(consequent)
 * @property int|null $a
 * @property int|null $b
 * @property int|null $c
 * @property int|null $d
 * @property float|null $confidence = null
 * @property float|null $support = null
 * @property float|null $lift = null
 * @property int|null $antecedentRuleAttributes = null
 * @property bool $inRuleClipboard
 * @property-read array $basicDataArr
 */
class Rule extends Entity{

  /**
   * @return array
   */
  public function getBasicDataArr() {
    $result=[
      'id'=>$this->ruleId,
      'text'=>$this->text,
      'a'=>$this->a,
      'b'=>$this->b,
      'c'=>$this->c,
      'd'=>$this->d,
      'selected'=>($this->inRuleClipboard?'1':'0')
    ];
    if ($this->confidence!=null){
      $result['confidence']=$this->confidence;
    }
    if ($this->support!=null){
      $result['support']=$this->support;
    }
    if ($this->lift!=null){
      $result['lift']=$this->lift;
    }
    if ($this->antecedentRuleAttributes!==null){
      $result['antecedentRuleAttributes']=$this->antecedentRuleAttributes;
    }
    if (!empty($this->row->task_id)){
      $result['task']=$this->row->task_id;
    }
    return $result;
  }

  /**
   * Method returning the relation of this rule to a concrete RuleSet
   * @param $ruleSet
   * @return RuleSetRuleRelation
   */
  public function getRuleSetRelation($ruleSet){
    if ($ruleSet instanceof RuleSet){
      $ruleSet=$ruleSet->ruleSetId;
    }
    if (!empty($this->ruleSetRuleRelations)){
      foreach($this->ruleSetRuleRelations as $ruleSetRuleRelation){
        if ($ruleSetRuleRelation->ruleSet->ruleSetId==$ruleSet){
          return $ruleSetRuleRelation;
        }
      }
    }
    return null;
  }

  public function getAntecedentRuleAttrbutes(){
    return $this->row->antecedent_rule_attributes;
  }

  public function setAntecedentRuleAttributes($value){
    if ($value===null){
      $this->row->antecedent_rule_attributes=null;
    }else{
      $this->row->antecedent_rule_attributes=$value;
    }
  }

  /**
   * @return array
   */
  public function getRuleHeadDataArr(){
    return [
      'text'=>$this->text,
      'task_id'=>$this->task->taskId,
      'pmml_rule_id'=>$this->pmmlRuleId,
      'a'=>$this->a,
      'b'=>$this->b,
      'c'=>$this->c,
      'd'=>$this->d
    ];
  }

}