<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Facades\RulesFacade;
use Nette\InvalidArgumentException;
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

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit,true);
    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=array('text'=>$rule->text,'FUI'=>$rule->confidence,'SUPP'=>$rule->support,'LIFT'=>$rule->lift,
          'a'=>$rule->a,'b'=>$rule->b,'c'=>$rule->c,'d'=>$rule->d,'selected'=>($rule->inRuleClipboard?'1':'0'));
      }
    }
    $this->sendJsonResponse(array('task'=>array('rulesCount'=>$this->rulesFacade->getRulesCountByTask($task,true),'IMs'=>$task->getInterestMeasures()),'rules'=>$rulesArr));
  }

  /**
   * Akce pro přidání pravidla do rule clipboard
   * @param int $miner
   * @param string $task
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   */
  public function actionAddRule($miner,$task,$rules){
    $rules=$this->changeRulesClipboardState($miner,$task,$rules,true);
    $result=array();
    if (!empty($rules)){
      foreach($rules as $rule){
        $result[]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * Akce pro odebrání pravidla z rule clipboard
   * @param int $miner
   * @param string $task
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   */
  public function actionRemoveRule($miner,$task,$rules){
    $rules=$this->changeRulesClipboardState($miner,$task,$rules,false);
    $result=array();
    if (!empty($rules)){
      foreach($rules as $rule){
        $result[]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * @param int $miner
   * @param string $task
   * @param int|string $rules
   * @param bool $inRuleClipboard
   * @throws ForbiddenRequestException
   * @return Rule[]
   */
  private function changeRulesClipboardState($miner,$task,$rules,$inRuleClipboard){
    $this->checkMinerAccess($miner);
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    $result=array();
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskUuid!=$task){
            throw new InvalidArgumentException;
          }
          if ($ruleTask->miner->minerId!=$miner){
            throw new InvalidArgumentException;
          }
          if ($rule->inRuleClipboard!=$inRuleClipboard){
            $rule->inRuleClipboard=$inRuleClipboard;
            $this->rulesFacade->saveRule($rule);
          }
          $result[]=$rule;
        }catch (\Exception $e){continue;}
      }
    }
    return $result;
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