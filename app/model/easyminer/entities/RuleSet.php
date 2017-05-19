<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Model\EasyMiner\Authorizators\IOwnerResource;
use LeanMapper\Entity;
use LeanMapper\Filtering;
use LeanMapper\Fluent;

/**
 * Class RuleSet
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $ruleSetId
 * @property string $name = ''
 * @property string $description = ''
 * @property int $rulesCount = 0
 * @property User $user m:hasOne
 * @property \DateTime|null $lastModified = null
 * @property-read RuleSetRuleRelation[] $ruleSetRuleRelations m:belongsToMany
 */
class RuleSet extends Entity implements IOwnerResource{

  /**
   * Method returning an array with basic data properties
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->ruleSetId,
      'name'=>$this->name,
      'description'=>(!empty($this->description)?$this->description:""),
      'rulesCount'=>$this->rulesCount,
      'lastModified'=>(!empty($this->lastModified))?$this->lastModified->format('c'):null
    ];
  }

  /**
   * Method returning Rules with the relation to this RuleSet (optionally depending on the given relationType)
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
   * Method returning the relations of this RuleSet to Rules (depending on the given relation type)
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

  /**
   * Method returning ID of the owner (User)
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