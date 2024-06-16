<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleSetRuleRelationsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleSetsRepository;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;
use Nette\Utils\Strings;

/**
 * Class RuleSetsFacade - třída pro práci s pravidly v DB
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RuleSetsFacade {
  /** @var  RuleSetsRepository $rulesRepository */
  private $ruleSetsRepository;
  /** @var  RuleSetRuleRelationsRepository $ruleSetRuleRelationsRepository */
  private $ruleSetRuleRelationsRepository;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * Method for cloning of existing RuleSet
   * @param RuleSet $ruleSet
   * @param bool $cloneRules
   * @return RuleSet
   * @throws \LeanMapper\Exception\InvalidArgumentException
   */
  public function cloneRuleSet(RuleSet $ruleSet,$ruleSetName='',$cloneRules=true){
    $result=new RuleSet();
    $result->user=$ruleSet->user;
    $result->name=!empty($ruleSetName)?$ruleSetName:$ruleSet->name.' clone '.date('c');
    $result->rulesCount=$ruleSet->rulesCount;
    $result->lastModified=new \DateTime();
    $this->saveRuleSet($result);

    if ($ruleSet->rulesCount>0){
      foreach ($ruleSet->ruleSetRuleRelations as $ruleSetRuleRelation){
        $resultRuleRelation=new RuleSetRuleRelation();
        $resultRuleRelation->ruleSet=$result;
        $resultRuleRelation->rule=$this->rulesFacade->cloneRule($ruleSetRuleRelation->rule);
        $resultRuleRelation->relation=$ruleSetRuleRelation->relation;
        $this->ruleSetRuleRelationsRepository->persist($resultRuleRelation);
      }
    }

    return $result;
  }

  /**
   * Method for adding/updating a relation between Rule and RuleSet
   * @param Rule|int $rule
   * @param RuleSet|int $ruleSet
   * @param string $relation
   * @param bool $updateRulesCount = true
   * @return bool
   * @throws \Exception
   */
  public function addRuleToRuleSet($rule,$ruleSet,$relation, $updateRulesCount=true){
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
    if ($updateRulesCount) $this->updateRuleSetRulesCount($ruleSet);
    return $result;
  }

  /**
   * Method for deleting the relation between Rule and RuleSet
   * @param Rule|int $rule
   * @param RuleSet|int $ruleSet
   * @param bool $updateRulesCount = true
   * @return bool
   * @throws \Exception
   * @throws \LeanMapper\Exception\InvalidStateException
   */
  public function removeRuleFromRuleSet($rule,$ruleSet,$updateRulesCount=true){
    if ($rule instanceof Rule){
      $rule=$rule->ruleId;
    }
    if ($ruleSet instanceof RuleSet){
      $ruleSet=$ruleSet->ruleSetId;
    }
    $ruleSetRuleRelation=$this->ruleSetRuleRelationsRepository->findBy(['rule_id'=>$rule,'rule_set_id'=>$ruleSet]);
    $result=$this->ruleSetRuleRelationsRepository->delete($ruleSetRuleRelation);
    if ($updateRulesCount) $this->updateRuleSetRulesCount($ruleSet);
    return $result;
  }

  /**
   * Method for removing all rules from the ruleset (deleting the relations between rules and rulesets; optionally only with selected type of relation)
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
   * Method for adding all rules from a Task in RuleClipboard to RuleSet
   * @param Task|int $task
   * @param RuleSet|int $ruleSet
   * @param string $relation
   */
  public function addAllRuleClipboardRulesToRuleSet($task,$ruleSet,$relation=RuleSetRuleRelation::RELATION_POSITIVE){
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    $rules=$this->rulesFacade->findRulesByTask($task,null,null,null,null,true);
    if (!empty($rules)){
      foreach($rules as $rule){
        $this->addRuleToRuleSet($rule,$ruleSet,$relation);
      }
    }
    $this->updateRuleSetRulesCount($ruleSet);
  }

  /**
   * Method for removing all rules from a Task in RuleClipboard from RuleSet
   * @param $task
   * @param $ruleSet
   */
  public function removeAllRuleClipboardRulesFromRuleSet($task,$ruleSet){
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    $rules=$this->rulesFacade->findRulesByTask($task,null,null,null,null,true);
    if (!empty($rules)){
      foreach($rules as $rule){
        $this->removeRuleFromRuleSet($rule,$ruleSet);
      }
    }
    $this->updateRuleSetRulesCount($ruleSet);
  }

  /**
   * Method returning rules from given ruleset
   * @param RuleSet|int $ruleSet
   * @param string $order
   * @param string|array|null $search
   * @param string|null $rel
   * @param null|int $offset
   * @param null|int $limit
   * @return Rule[]
   */
  public function findRulesByRuleSet($ruleSet, $search=null, $rel=null, $order=null, $offset=null, $limit=null){
    $params=$this->prepareFindRulesParams([],$search,$rel);

    if (!empty($order)){
      $params['order']=$this->getRulesOrderFormula($order);
    }

    return $this->ruleSetRuleRelationsRepository->findAllRulesByRuleSet($ruleSet,$params,$offset,$limit);
  }

  /**
   * Method returning count rules from given ruleset
   * @param RuleSet|int $ruleSet
   * @param string|array|null $search
   * @param string|null $rel
   * @return int
   */
  public function findRulesCountByRuleSet($ruleSet, $search=null, $rel=null){
    $params=$this->prepareFindRulesParams([],$search,$rel);
    if (!empty($order)){
      $params['order']=$this->getRulesOrderFormula($order);
    }

    return $this->ruleSetRuleRelationsRepository->findRulesCountByRuleSet($ruleSet,$params);
  }

  /**
   * Metoda pro přípravu parametrů pro vyhledávání pravidel
   * @param array $params
   * @param string|array|null $search
   * @param string|null $rel = null
   * @return array
   */
  private function prepareFindRulesParams(array $params, $search, $rel=null){
    if (!empty($search)){
      if (is_array($search)){
        if (!empty($search['antecedent']) || !empty($search['consequent'])){
          $params[] = ['text LIKE ?', (!empty($search['antecedent']) ? '%' . $search['antecedent'] . '%→' : '%→') . (!empty($search['consequent']) ? '%' . $search['consequent'] . '%' : '%')];
        }elseif (count($search) > 0){
          foreach ($search as $searchItem){
            if (!empty($searchItem)){
              $params[] = ['text LIKE ?', '%' . $searchItem . '%'];
            }
          }
        }
      }else{
        $params[] = ['text LIKE ?', '%' . $search . '%'];
      }
    }
    if (!empty($rel)){
      $params['relation']=$rel;
    }
    return $params;
  }

  /**
   * Private method returning order formula for ordering of rules by selected interest measure
   * @param $order
   * @return string
   */
  private function getRulesOrderFormula($order){
    $formulasArr=[
      'default'=>'rule_id ASC',
      'default ASC'=>'rule_id ASC',
      'default DESC'=>'rule_id DESC',
      'fui'=>'confidence DESC',
      'conf'=>'confidence DESC',
      'conf ASC'=>'confidence ASC',
      'confidence'=>'confidence DESC',
      'supp'=>'support DESC',
      'supp ASC'=>'support ASC',
      'support'=>'support DESC',
      'add'=>'lift DESC',
      'lift'=>'lift DESC',
      'lift ASC'=>'lift ASC',
      'a'=>'a DESC',
      'b'=>'b DESC',
      'c'=>'c DESC',
      'd'=>'d DESC',
      'cba'=>'cba',
      'cba ASC'=>'cba',
      'cba DESC'=>'cba DESC'
    ];
    $order=Strings::lower($order);
    if (isset($formulasArr[$order])){
      return $formulasArr[$order];
    }else{
      return 'rule_id';
    }
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
    if (empty($ruleSet->lastModified)){
      $ruleSet->lastModified=new \DateTime();
    }
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
   * @param Rule|int $rule
   * @return bool|RuleSetRuleRelation
   */
  public function findRuleSetRuleRelation($ruleSet, $rule){
    try{
      $result=$this->ruleSetRuleRelationsRepository->findBy(['rule_id'=>($rule instanceof Rule?$rule->ruleId:$rule),'rule_set_id'=>($ruleSet instanceof RuleSet?$ruleSet->ruleSetId:$ruleSet)]);
      return $result;
    }catch (\Exception $e){
      return false;
    }
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
   * Method for check, if there exists a ruleset with the given name and the same owner
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
    //check, if there exists a ruleset with the given name
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
   * Method for saving new ruleset for the given user
   * (if the name is not unique, the method adds a number to the end of the name)
   * @param string $ruleSetName
   * @param User $user
   * @return RuleSet
   */
  public function saveNewRuleSetForUser($ruleSetName,User $user){
    //vyřešení unikátního jména
    $newName=$ruleSetName;
    $needCheck=true;
    $counter=2;
    while($needCheck){
      try{
        $this->checkUniqueRuleSetNameByUser($newName,$user);
        $needCheck=false;
      }catch (InvalidArgumentException $e){
        $needCheck=true;
        $newName=$ruleSetName.' '.$counter;
        $counter++;
      }
    }
    //save it...
    $ruleSet=new RuleSet();
    $ruleSet->user=$user;
    $ruleSet->name=$newName;
    $this->saveRuleSet($ruleSet);
    return $ruleSet;
  }

  /**
   * Method for updating of count of rules in the given ruleset
   * @param RuleSet|int $ruleSet
   */
  public function updateRuleSetRulesCount($ruleSet){
    if (!($ruleSet instanceof RuleSet)){
      $ruleSet=$this->findRuleSet($ruleSet);
    }
    $ruleSet->rulesCount=$this->ruleSetRuleRelationsRepository->findCountRulesByRuleSet($ruleSet);
    $ruleSet->lastModified=new \DateTime();
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