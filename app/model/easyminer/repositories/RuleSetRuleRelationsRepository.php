<?php

namespace App\Model\EasyMiner\Repositories;

use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleSet;
use LeanMapper\Fluent;

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

  /**
   * Funkce vracející pravidla patřící do daného rulesetu
   * @param RuleSet $ruleSet
   * @param null|string $order
   * @param null|int $offset
   * @param null|int $limit
   * @return Rule[]
   */
  public function findAllRulesByRuleSet(RuleSet $ruleSet,$order=null,$offset=null,$limit=null){
    /** @var Fluent $query */
    $query=$this->connection->select('rules.*')
      ->from('rules')
      ->leftJoin('rule_set_rule_relations')->on('rules.rule_id=%n.rule_id',$this->getTable())
      ->where('rule_set_id = ?',$ruleSet->ruleSetId);
    if ($order){
      $query=$query->orderBy($order);
    }
    $this->createEntities($query->fetchAll($offset, $limit),$this->mapper->getEntityClass('rules'));
  }

  /**
   * Funkce vracející počet pravidel patřících do daného rulesetu
   * @param RuleSet $ruleSet
   * @return int
   */
  public function findCountRulesByRuleSet(RuleSet $ruleSet){
    /** @var Fluent $query */
    $query=$this->connection->select('count(rules.*) as pocet')
      ->from('rules')
      ->leftJoin('rule_set_rule_relations')->on('rules.rule_id=%n.rule_id',$this->getTable())
      ->where('rule_set_id = ?',$ruleSet->ruleSetId);
    return $query->fetchSingle();
  }


}