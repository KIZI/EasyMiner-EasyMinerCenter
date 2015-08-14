<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;

/**
 * Class RuleSet
 *
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int $ruleSetId
 * @property string $name
 * @property string $description
 * @property int $rulesCount = 0
 * @property User $user m:hasOne
 * @property-read RuleSetRuleRelation[] $ruleSetRuleRelations m:belongsToMany
 */
class RuleSet extends Entity{

  /**
   * Funkce vracející základní data v podobě pole
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->ruleSetId,
      'name'=>$this->name,
      'description'=>(!empty($this->description)?$this->description:""),
      'rulesCount'=>$this->rulesCount
    ];
  }

  /**
   * Funkce vracející pravidla zařazená do tohoto rulesetu
   * @param null|string $relationType=null
   * @return Rule[]
   */
  public function findRules($relationType=null){
    if ($relationType){
      $ruleSetRuleRelations=$this->findRuleRelationsByType($relationType);
    }else{
      $ruleSetRuleRelations=$this->ruleSetRuleRelations;
    }
    $rulesArr=[];
    if (!empty($ruleSetRuleRelations)){
      foreach($ruleSetRuleRelations as $ruleRelation){
        $rule=$ruleRelation->rule;
        $rulesArr[$rule->ruleId]=$rule;
      }
    }
    return $rulesArr;
  }

  /**
   * Funkce vracející relace aktuálního rulesetu k pravidlům (v závislosti na zvoleném typu relace)
   * @param string $relationType
   * @return RuleSetRuleRelation[]
   * @throws \LeanMapper\Exception\Exception
   */
  public function findRuleRelationsByType($relationType){
    $ruleSetRuleRelations = $this->getValueByPropertyWithRelationship('values', null, new Filtering(function (Fluent $statement)use($relationType){
      $statement->where("relation = %s", $relationType);
      $statement->limit(1);
    }));
    return $ruleSetRuleRelations;
  }

} 