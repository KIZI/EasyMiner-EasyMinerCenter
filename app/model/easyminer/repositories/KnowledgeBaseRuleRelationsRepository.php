<?php

namespace EasyMinerCenter\Model\EasyMiner\Repositories;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use LeanMapper\Fluent;

class KnowledgeBaseRuleRelationsRepository extends BaseRepository{

    /**
     * Removes all records, where were rules compared with rules from ruleSet
     * @param $ruleSetId
     */
    public function deleteAllByRuleSet($ruleSetId){
        $this->connection->query(
            'DELETE FROM %n WHERE %n = ?', $this->getTable(), 'rule_set_id', $ruleSetId
        );
    }

    /**
     * Removes all records, where were rules compared with rules from param
     * @param array $ruleIds
     */
    public function deleteAllByRuleSetRules($ruleIds){
        $this->connection->query(
            'DELETE FROM %n WHERE %n IN (?)', $this->getTable(), 'knowledge_base_rule_id', $ruleIds
        );
    }


}