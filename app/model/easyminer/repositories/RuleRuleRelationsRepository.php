<?php

namespace EasyMinerCenter\Model\EasyMiner\Repositories;

use LeanMapper\Connection;

class RuleRuleRelationsRepository{

    const
        TABLE_NAME = 'rule_rule_relations',
        COLUMN_RULE_SET = 'rule_set_id',
        COLUMN_RULE = 'rule_id',
        COLUMN_RULESET_RULE = 'rule_set_rule_id',
        COLUMN_RELATION = 'relation',
        COLUMN_RATE = 'rate';

    /** @var  Connection $connection */
    private $connection;

    public function __construct(Connection $connection){
        $this->connection=$connection;
    }

    /**
     * Get all history records of comparing of rule with ruleset as associate array with rule_set_rule_id key
     * @param $ruleId
     * @param $ruleSetId
     * @return array
     */
    public function getComparingHistory($ruleId, $ruleSetId){
        return $this->connection->select(self::COLUMN_RULESET_RULE.','.self::COLUMN_RELATION.','.self::COLUMN_RATE)
            ->from(self::TABLE_NAME)->where(self::COLUMN_RULE_SET.' = ?',$ruleSetId)
            ->where(self::COLUMN_RULE.' = ?',$ruleId)
            ->orderBy(self::COLUMN_RATE.' DESC')->fetchAssoc(self::COLUMN_RULESET_RULE);
    }

    /**
     * Removes all records, where were rules compared with rules from ruleSet
     * @param $ruleSetId
     */
    public function deleteAllByRuleSet($ruleSetId){
        $this->connection->query(
            'DELETE FROM %n WHERE %n = ?', self::TABLE_NAME, self::COLUMN_RULE_SET, $ruleSetId
        );
    }

    /**
     * Removes all records, where were rules compared with rules from param
     * @param array $ruleIds
     */
    public function deleteAllByRuleSetRules($ruleIds){
        $this->connection->query(
            'DELETE FROM %n WHERE %n IN (?)', self::TABLE_NAME, self::COLUMN_RULESET_RULE, implode(',',$ruleIds)
        );
    }

    /**
     * Multiinsert of comparing results
     * @param $data values to be inserted
     */
    public function saveComparing($data){
        array_unshift($data, "INSERT INTO " . self::TABLE_NAME);
        $this->connection->query($data);
    }

}