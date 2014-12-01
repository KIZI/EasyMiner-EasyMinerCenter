<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Repositories\RulesRepository;


/**
 * Class RulesFacade - třída pro práci s pravidly v DB
 * @package App\Model\EasyMiner\Facades
 */
class RulesFacade {
  /** @var  RulesRepository $rulesRepository */
  private $rulesRepository;

  /**
   * @param $ruleId
   * @return Rule
   */
  public function findRule($ruleId){
    return $this->rulesRepository->find($ruleId);
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
  public function saveRule(Rule $rule){
    $this->rulesRepository->persist($rule);
  }


  public function __construct(RulesRepository $rulesRepository){
    $this->rulesRepository=$rulesRepository;
  }
} 