<?php

namespace App\KnowledgeBaseModule\Presenters;

use App\Model\EasyMiner\Entities\RuleSet;
use App\Model\EasyMiner\Entities\RuleSetRuleRelation;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\EasyMiner\Facades\RuleSetsFacade;
use App\Model\EasyMiner\Facades\UsersFacade;
use App\Presenters\BaseRestPresenter;
use Nette\InvalidArgumentException;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package App\KnowledgeBaseModule\Presenters
 */
class RuleSetsPresenter extends BaseRestPresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  /**
   * Akce pro vypsání existujících rulesetů
   */
  public function actionList(){
    $ruleSets=$this->ruleSetsFacade->findRuleSetsByUser($this->user->id);
    $result=[];
    if (!empty($ruleSets)){
      foreach ($ruleSets as $ruleSet){
        $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
      }
    }
    $this->sendJsonResponse($result);
  }

  #region akce pro manipulaci s rulesetem

  /**
   * Akce pro vytvoření nového rulesetu (se zadaným jménem)
   * @param string $name
   * @throws InvalidArgumentException
   */
  public function actionNew($name){
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id);
    //vytvoření rulesetu
    $ruleSet=new RuleSet();
    $ruleSet->name=$name;
    $ruleSet->user=$this->usersFacade->findUser($this->user->id);
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Akce pro přejmenování existujícího rulesetu
   * @param int $id
   * @param string $name
   */
  public function actionRename($id,$name){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id,$ruleSet);
    //změna a uložení
    $ruleSet->name=$name;
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Akce pro smazání konkrétního rulesetu
   * @param int $id
   */
  public function actionDelete($id){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //smazání
    if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  #endregion akce pro manipulaci s rulesetem

  #region akce pro manipulaci s pravidly v rulesetech

  /**
   * Akce vracející jeden konkrétní ruleset se základním přehledem pravidel
   * @param int $id
   * @param int $offset
   * @param int $limit
   * @param string|null $order = null
   */
  public function actionGetRules($id,$offset=0,$limit=25,$order=null){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //připravení výstupu
    $result=[
      'ruleset'=>$ruleSet->getDataArr(),
      'rules'=>[]
    ];
    if ($ruleSet->rulesCount>0 || true){
      $rules=$this->ruleSetsFacade->findRulesByRuleSet($ruleSet,$order,$offset,$limit);
      if (!empty($rules)){
        foreach($rules as $rule){
          $result['rules'][$rule->ruleId]=['text'=>$rule->text];
        }
      }
    }
    $this->sendJsonResponse($result);
  }

  /**
   * Akce pro přidání pravidel do rulesetu
   * @param int $id
   * @param string|int $rules - ID pravidel oddělená čárkami či středníky
   * @param string $relation = 'positive'
   */
  public function actionAddRules($id,$rules,$relation=RuleSetRuleRelation::RELATION_POSITIVE){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        //try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->addRuleToRuleSet($rule,$ruleSet,$relation);
        //}catch (\Exception $e){continue;}
      }
    }
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Akce pro odebrání pravidel z rulesetu
   * @param int $id
   * @param string|int $rules
   */
  public function actionRemoveRules($id,$rules){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        //try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleSet);
        //}catch (\Exception $e){continue;}
      }
    }
    $this->ruleSetsFacade->updateRuleSetRulesCount($rule,$ruleSet);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Akce pro odebrání všech pravidel z rulesetu
   * @param int $id
   */
  public function actionRemoveAllRules($id){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //smazání
    $this->ruleSetsFacade->removeAllRulesFromRuleSet($ruleSet);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  #endregion akce pro manipulaci s pravidly v rulesetech

  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  #endregion injections
}