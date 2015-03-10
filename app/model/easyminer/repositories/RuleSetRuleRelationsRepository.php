<?php

namespace App\Model\EasyMiner\Repositories;

use App\Model\EasyMiner\Entities\RuleSet;

class RuleSetRuleRelationsRepository extends BaseRepository{

  /**
   * Funkce pro smazání všech vazeb pravidel k danému rulesetu
   * @param RuleSet $ruleSet
   * @return \DibiResult|int
   */
  public function deleteAllByRuleSet(RuleSet $ruleSet){
    return $this->connection->query(
      'DELETE FROM %n WHERE %n = ?', $this->getTable(), 'rule_set_id', $ruleSet->ruleSetId
    );
  }

}