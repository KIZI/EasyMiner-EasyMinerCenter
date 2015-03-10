<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class RuleSet
 *
 * @package App\Model\EasyMiner\Entities
 * @property int $ruleSetId
 * @property string $name
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
      'rule_set_id'=>$this->ruleSetId,
      'name'=>$this->name,
      'rules'=>$this->rulesCount
    ];
  }

} 