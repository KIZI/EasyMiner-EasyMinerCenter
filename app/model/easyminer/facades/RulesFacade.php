<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\Cedent;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleAttribute;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Repositories\CedentsRepository;
use App\Model\EasyMiner\Repositories\RuleAttributesRepository;
use App\Model\EasyMiner\Repositories\RulesRepository;


/**
 * Class RulesFacade - třída pro práci s pravidly v DB
 * @package App\Model\EasyMiner\Facades
 */
class RulesFacade {
  /** @var  RulesRepository $rulesRepository */
  private $rulesRepository;
  /** @var  CedentsRepository $cedentsRepository */
  private $cedentsRepository;
  /** @var  RuleAttributesRepository $ruleAttributesRepository */
  private $ruleAttributesRepository;

  /**
   * @param $ruleId
   * @return Rule
   */
  public function findRule($ruleId){
    return $this->rulesRepository->find($ruleId);
  }

  /**
   * @param Task|int $task
   * @param string $order
   * @param int $offset = null
   * @param int $limit = null
   * @param bool $onlyInClipboard = false
   * @return Rule[]
   */
  public function findRulesByTask($task,$order,$offset=null,$limit=null,$onlyInClipboard=false){
    if ($task instanceof Task){
      $task=$task->taskId;
    }
    $params=array('task_id'=>$task);
    if (!empty($order)){
      $params['order']=$order;
    }
    if ($onlyInClipboard){
      $params['in_rule_clipboard']=true;
    }
    return $this->rulesRepository->findAllBy($params,$offset,$limit);
  }

  /**
   * @param Task|int $task
   * @param bool $onlyInClipboard = false
   * @return Rule[]
   */
  public function getRulesCountByTask($task,$onlyInClipboard=false){
    if ($task instanceof Task){
      $task=$task->taskId;
    }
    $params=array('task_id'=>$task);
    if ($onlyInClipboard){
      $params['in_rule_clipboard']=true;
    }
    return $this->rulesRepository->findCountBy($params);
  }

  /**
   * @param int|Rule $rule
   */
  public function deleteRule($rule){
    if (!$rule instanceof Rule){
      $rule=$this->findRule($rule);
    }
    $this->rulesRepository->delete($rule);
  }

  /**
   * @param Rule $rule
   */
  public function saveRule(Rule &$rule){
    $this->rulesRepository->persist($rule);
  }


  public function saveCedent(Cedent &$cedent){
    $this->cedentsRepository->persist($cedent);
  }

  public function saveRuleAttribute(RuleAttribute &$ruleAttribute){
    $this->ruleAttributesRepository->persist($ruleAttribute);
  }

  public function calculateMissingInterestMeasures(){
    $this->rulesRepository->calculateMissingInterestMeasures();
  }

  /**
   * @param Task $task
   * @param bool $inRuleClipboard
   */
  public function changeAllTaskRulesClipboardState(Task $task, $inRuleClipboard){
    $this->rulesRepository->changeTaskRulesClipboardState($task->taskId,$inRuleClipboard);
  }

  public function __construct(RulesRepository $rulesRepository, CedentsRepository $cedentsRepository, RuleAttributesRepository $ruleAttributesRepository){
    $this->rulesRepository=$rulesRepository;
    $this->cedentsRepository=$cedentsRepository;
    $this->ruleAttributesRepository=$ruleAttributesRepository;
  }
} 