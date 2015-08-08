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
  public function deleteAllByRuleSet(RuleSet $ruleSet){//TODO missing param $relation
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
    //TODO najít jiný způsob vytváření entit (chyba v LeanMapper\Repository)
    $relevantTable='rules';

    /** @var Fluent $query */
    $query=$this->connection->select($relevantTable.'.*')
      ->from($relevantTable)
      ->leftJoin('rule_set_rule_relations')->on($relevantTable.'.rule_id=%n.rule_id',$this->getTable())
      ->where('rule_set_id = ?',$ruleSet->ruleSetId);
    if ($order){
      $query=$query->orderBy($order);
    }
    $entityClass=$this->mapper->getEntityClass($relevantTable);


    $ruleRows=$query->fetchAll($offset, $limit);
    $result=[];
    foreach ($ruleRows as $ruleRow){
      $result[]=$this->createEntity($ruleRow,$entityClass,$relevantTable);
    }
    return $this->entityFactory->createCollection($result);
  }

  /**
   * Funkce vracející počet pravidel patřících do daného rulesetu
   * @param RuleSet $ruleSet
   * @return int
   */
  public function findCountRulesByRuleSet(RuleSet $ruleSet){
    /** @var Fluent $query */
    $query=$this->connection->select('count(*) as pocet')
      ->from('rules')
      ->join('rule_set_rule_relations')->on('rules.rule_id=%n.rule_id','rule_set_rule_relations')
      ->where('rule_set_id = ?',$ruleSet->ruleSetId);
    $result=$query->fetchSingle();
    return $result;
  }


}