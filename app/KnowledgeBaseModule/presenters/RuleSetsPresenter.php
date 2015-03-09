<?php

namespace App\KnowledgeBaseModule\Presenters;

use App\Model\EasyMiner\Entities\RuleSet;
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
      $this->sendJsonResponse(['state'=>'deleted']);
    }
  }

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