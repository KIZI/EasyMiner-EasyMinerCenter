<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleSet;
use App\Model\EasyMiner\Entities\RuleSetRuleRelation;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\RuleSetRuleRelationsRepository;
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
  /** @var  RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository */
  private $ruleSetRuleRelationsRepository;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * Funkce pro přidání/změnu relace Rule k RuleSet
   * @param Rule|int $rule
   * @param RuleSet|int $ruleSet
   * @param string $relation
   * @return bool
   * @throws \Exception
   */
  public function addRuleToRuleSet($rule,$ruleSet,$relation){
    if (!($rule instanceof Rule)){
      $rule=$this->rulesFacade->findRule($rule);
    }
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->ruleSetsRepository->find($ruleSet);
    }
    try{
      $ruleSetRuleRelation=$this->ruleSetRuleRelationsRepository->findBy(['rule_id'=>$rule->ruleId,'rule_set_id'=>$ruleSet->ruleSetId]);
    }catch (\Exception $e){
      $ruleSetRuleRelation=new RuleSetRuleRelation();
      $ruleSetRuleRelation->rule=$rule;
      $ruleSetRuleRelation->ruleSet=$ruleSet;
    }
    $ruleSetRuleRelation->relation=$relation;
    $result=$this->ruleSetRuleRelationsRepository->persist($ruleSetRuleRelation);
    $this->updateRuleSetRulesCount($ruleSet);
    return $result;
  }

  /**
   * Funkce pro odebrání pravidla z rulesetu
   * @param Rule|int $rule
   * @param RuleSet|int $ruleSet
   * @return bool
   * @throws \Exception
   * @throws \LeanMapper\Exception\InvalidStateException
   */
  public function removeRuleFromRuleSet($rule,$ruleSet){
    if ($rule instanceof Rule){
      $rule=$rule->ruleId;
    }
    if ($ruleSet instanceof RuleSet){
      $ruleSet=$ruleSet->ruleSetId;
    }
    $ruleSetRuleRelation=$this->ruleSetRuleRelationsRepository->findBy(['rule_id'=>$rule,'rule_set_id'=>$ruleSet]);
    $result=$this->ruleSetRuleRelationsRepository->delete($ruleSetRuleRelation);
    $this->updateRuleSetRulesCount($ruleSet);
    return $result;
  }

  /**
   * Funkce pro smazání všech pravidel z daného datasetu (volitelně v závislosti na definované relaci)
   * @param RuleSet|int $ruleSet
   * @param string|null $relation = null
   * @return bool
   * @throws \Exception
   */
  public function removeAllRulesFromRuleSet($ruleSet,$relation=null){
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    $result=$this->ruleSetRuleRelationsRepository->deleteAllByRuleSet($ruleSet,$relation);
    $this->updateRuleSetRulesCount($ruleSet);
    return $result;
  }

  /**
   * @param RuleSet|int $ruleSet
   * @param string $order
   * @param null|int $offset
   * @param null|int $limit
   * @return Rule[]
   */
  public function findRulesByRuleSet($ruleSet,$order,$offset=null,$limit=null){
    return $this->ruleSetRuleRelationsRepository->findAllRulesByRuleSet($ruleSet,$order,$offset,$limit);
  }
  
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
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    if ($user instanceof User){
      $user=$user->userId;
    }
    if ($ruleSet->user->userId!=$user){
      throw new BadRequestException('You are not authorized to access selected ruleset!');
    }
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
   * Funkce pro přepočítání počtu pravidel v rulesetu
   * @param RuleSet $ruleSet
   */
  private function updateRuleSetRulesCount(RuleSet $ruleSet){
    $ruleSet->rulesCount=$this->ruleSetRuleRelationsRepository->findCountRulesByRuleSet($ruleSet);
    $this->saveRuleSet($ruleSet);
  }

  /**
   * @param RuleSetsRepository $ruleSetsRepository
   * @param RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository
   * @param RulesFacade $rulesFacade
   */
  public function __construct(RuleSetsRepository $ruleSetsRepository, RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository, RulesFacade $rulesFacade){
    $this->ruleSetsRepository=$ruleSetsRepository;
    $this->ruleSetRuleRelationsRepository=$ruleSetRuleRelationsRepository;
    $this->rulesFacade=$rulesFacade;
  }
} 