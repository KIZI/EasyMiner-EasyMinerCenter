<?php

namespace EasyMinerCenter\Model\EasyMiner\Repositories;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use LeanMapper\Fluent;

/**
 * Class RuleSetRuleRelationsRepository
 * @package EasyMinerCenter\Model\EasyMiner\Repositories
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RuleSetRuleRelationsRepository extends BaseRepository{

  /**
   * Method for removing of all relations between Rules and the given RuleSet
   * @param RuleSet $ruleSet
   * @return \DibiResult|int
   */
  public function deleteAllByRuleSet(RuleSet $ruleSet){//TODO missing param $relation
    return $this->connection->query(
      'DELETE FROM %n WHERE %n = ?', $this->getTable(), 'rule_set_id', $ruleSet->ruleSetId
    );
  }

  /**
   * Method returning the Rules with relation to the given RuleSet
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
      #region vyřešení pravidel pro řazení výsledků
      if (in_array($order,['confidence','support','lift'])){
        $order=$order.' ASC';
      }elseif($order=='antecedent_rule_attributes'){
        $order='antecedent_rule_attributes DESC';
      }elseif($order=='cba'){
        $order='(antecedent_rule_attributes=0) ASC,confidence ASC,support ASC,antecedent_rule_attributes DESC';
      }
      #endregion vyřešení pravidel pro řazení výsledků

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
   * Method returning the count of Rules in relation to the given RuleSet
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