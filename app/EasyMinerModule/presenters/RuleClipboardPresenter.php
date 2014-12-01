<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Facades\RulesFacade;
use LeanMapper\Exception\InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class RuleClipboardPresenter - presenter pro práci s Rule clipboard v rámci EasyMineru
 * @package App\EasyMinerModule\Presenters
 */
class RuleClipboardPresenter  extends BasePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * Funkce vracející přehled úloh, které mají pravidla v RuleClipboard
   * @param int $miner
   */
  public function actionGetTasks($miner){
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $miner=$this->minersFacade->findMiner($miner);
    $this->checkMinerAccess($miner);

    $tasks=$miner->tasks;
    $result=array();
    if (!empty($tasks)){
      foreach ($tasks as $task){
        if ($task->inRuleClipboard){
          $result[$task->taskUuid]=$task->name;
        }
      }
    }
    $this->sendJsonResponse($result);
  }

  /**
   * Akce vracející pravidla pro vykreslení v easymineru
   * @param int $miner
   * @param string $task
   * @param $offset
   * @param $limit
   * @param $order
   */
  public function actionGetRules($miner,$task,$offset=0,$limit=25,$order='id'){
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->minersFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    //TODO akce pro vrácení části výsledků
  }

  /**
   * Akce pro přidání pravidla do rule clipboard
   * @param int $miner
   * @param string $task
   * @param int $rule
   */
  public function actionAddRule($miner,$task,$rule){
    $this->changeRulesClipboardState($miner,$task,$rule,true);
    $this->sendTextResponse('OK');
  }

  /**
   * Akce pro odebrání pravidla z rule clipboard
   * @param int $miner
   * @param string $task
   * @param int $rule
   */
  public function actionRemoveRule($miner,$task,$rule){
    $this->changeRulesClipboardState($miner,$task,$rule,false);
    $this->sendTextResponse('OK');
  }

  /**
   * @param int $miner
   * @param string $task
   * @param int|string $rule
   * @param bool $inRuleClipboard
   * @throws ForbiddenRequestException
   */
  private function changeRulesClipboardState($miner,$task,$rule,$inRuleClipboard){
    $this->checkMinerAccess($miner);
    $ruleIdsArr=explode(',',str_replace(';',',',$rule));
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($rule);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskUuid!=$task){
            throw new \Nette\InvalidArgumentException;
          }
          if ($ruleTask->miner->minerId!=$miner){
            throw new \Nette\InvalidArgumentException;
          }
          if ($rule->inRuleClipboard!=$inRuleClipboard){
            $rule->inRuleClipboard=$inRuleClipboard;
            $this->rulesFacade->saveRule($rule);
          }
        }catch (\Exception $e){continue;}
      }
    }
  }


  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  #endregion injections
} 