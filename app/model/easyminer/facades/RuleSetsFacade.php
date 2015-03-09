<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\RuleSet;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\RuleSetsRepository;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;

/**
 * Class RuleSetsFacade - třída pro práci s pravidly v DB
 * @package App\Model\EasyMiner\Facades
 */
class RuleSetsFacade {
  /** @var  RuleSetsRepository $rulesRepository */
  private $ruleSetsRepository;

  /**
   * @param int $ruleSetId
   * @return RuleSet
   * @throws \Exception
   */
  public function findRuleSet($ruleSetId){
    return $this->ruleSetsRepository->find($ruleSetId);
  }

  /**
   * @param User|int $user
   * @return RuleSet[]
   */
  public function findRuleSetsByUser($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $this->ruleSetsRepository->findAllBy(['user_id'=>$user,'order'=>'name']);
  }

  /**
   * @param RuleSet $ruleSet
   */
  public function saveRuleSet(RuleSet &$ruleSet){
    $this->ruleSetsRepository->persist($ruleSet);
  }

  /**
   * @param int|RuleSet $ruleSet
   * @return bool
   */
  public function deleteRuleSet($ruleSet){
    if (!$ruleSet instanceof RuleSet){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    return $this->ruleSetsRepository->delete($ruleSet);
  }

  /**
   * @param RuleSet|int $ruleSet
   * @param User|int $user
   * @throws BadRequestException
   */
  public function checkRuleSetAccess($ruleSet, $user){
    //TODO implement!!!
  }

  /**
   * Funkce pro kontrolu, zda už existuje u daného uživatele ruleset se zadaným názvem
   * @param string $ruleSetName
   * @param int|User $user
   * @param null|int|RuleSet $ignoreRuleSet
   * @throws InvalidArgumentException
   */
  public function checkUniqueRuleSetNameByUser($ruleSetName,$user,$ignoreRuleSet=null){
    if ($user instanceof User){
      $user=$user->userId;
    }
    if ($ignoreRuleSet instanceof RuleSet){
      $ignoreRuleSet=$ignoreRuleSet->ruleSetId;
    }
    //kontrola, jestli již existuje ruleset se zadaným názvem
    $existingRuleSets=$this->findRuleSetsByUser($user);
    if (!empty($existingRuleSets)){
      foreach($existingRuleSets as $existingRuleSet){
        if (($existingRuleSet->name==$ruleSetName)&&($existingRuleSet->ruleSetId!=$ignoreRuleSet)){
          throw new InvalidArgumentException('Rule set with the given name already exists!');
        }
      }
    }
  }

  /**
   * @param RuleSetsRepository $ruleSetsRepository
   */
  public function __construct(RuleSetsRepository $ruleSetsRepository){
    $this->ruleSetsRepository=$ruleSetsRepository;
  }
} 