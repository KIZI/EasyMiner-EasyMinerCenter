<?php

namespace EasyMinerCenter\Model\EasyMiner\Repositories;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;

class RulesRepository extends BaseRepository{

  /**
   * @param null|int $taskId
   * @param bool $forceCount=false
   */
  public function calculateMissingInterestMeasures($taskId=null, $forceCount=false){
    //TODO zkontrolovat vzorec liftu
    if ($taskId){
      if ($forceCount){
        $this->connection->query('UPDATE ['.$this->getTable().'] SET confidence=(a/(a+b)),support=(a/(a+b+c+d)),lift=((a*(a+b+c+d))/((a+b)*(a+c)))  WHERE task_id=%s',$taskId,';');
      }else {
        $this->connection->query('UPDATE [' . $this->getTable() . '] SET confidence=(a/(a+b)) WHERE task_id=%s', $taskId, ' AND confidence IS NULL;');
        $this->connection->query('UPDATE [' . $this->getTable() . '] SET support=(a/(a+b+c+d)) WHERE task_id=%s', $taskId, ' AND support IS NULL;');
        $this->connection->query('UPDATE [' . $this->getTable() . '] SET lift=((a*(a+b+c+d))/((a+b)*(a+c))) WHERE task_id=%s', $taskId, ' AND lift IS NULL;');
      }
    }else{
      $this->connection->query('UPDATE ['.$this->getTable().'] SET confidence=(a/(a+b)) WHERE confidence IS NULL;');
      $this->connection->query('UPDATE ['.$this->getTable().'] SET support=(a/(a+b+c+d)) WHERE support IS NULL;');
      $this->connection->query('UPDATE ['.$this->getTable().'] SET lift=((a*(a+b+c+d))/((a+b)*(a+c))) WHERE lift IS NULL;');
    }
  }

  /**
   * @param int $taskId
   * @param bool $inRuleClipboard
   */
  public function changeTaskRulesClipboardState($taskId,$inRuleClipboard){
    $this->connection->query('UPDATE ['.$this->getTable().'] SET in_rule_clipboard=%b',$inRuleClipboard,'WHERE task_id=%i',$taskId);
  }

  /**
   * @param Rule[] $rulesArr
   */
  public function insertRulesHeads($rulesArr){
    if (empty($rulesArr)){return;}
    //příprava
    $insertSql='INSERT INTO ['.$this->getTable().'] %m';
    $insertColumns=[];
    foreach ($rulesArr as $rule){
      $insertColumns=array_keys($rule->getRuleHeadDataArr());
      break;
    }

    $counter=0;
    $insertArr=[];
    foreach($insertColumns as $column){
      $insertArr[$column]=[];
    }
    foreach($rulesArr as $rule){
      $ruleDataArr=$rule->getRuleHeadDataArr();
      foreach($insertColumns as $column) {
        $insertArr[$column][]=$ruleDataArr[$column];
      }

      $counter++;
      if ($counter>500){
        $this->connection->query($insertSql,$insertArr);
        $counter=0;
        $insertArr=[];
        foreach($insertColumns as $column){
          $insertArr[$column]=[];
        }
      }
    }
    if (!empty($insertArr)){
      $this->connection->query($insertSql,$insertArr);
    }

    //FIXME implement!
  }
  
}