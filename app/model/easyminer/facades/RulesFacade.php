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
   * @return Rule[]
   */
  public function findRulesByTask($task,$order,$offset=null,$limit=null){
    if ($task instanceof Task){
      $task=$task->taskId;
    }
    return $this->rulesRepository->findAllBy(array('task_id'=>$task,'order'=>$order),$offset,$limit);
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

  public function __construct(RulesRepository $rulesRepository, CedentsRepository $cedentsRepository, RuleAttributesRepository $ruleAttributesRepository){
    $this->rulesRepository=$rulesRepository;
    $this->cedentsRepository=$cedentsRepository;
    $this->ruleAttributesRepository=$ruleAttributesRepository;
  }
} 