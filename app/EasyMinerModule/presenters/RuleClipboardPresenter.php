<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Entities\RuleSetRuleRelation;
use App\Model\EasyMiner\Facades\RuleSetsFacade;
use App\Model\EasyMiner\Facades\RulesFacade;
use App\Model\EasyMiner\Facades\TasksFacade;
use Nette\InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class RuleClipboardPresenter - presenter pro práci s Rule clipboard v rámci EasyMineru
 * @package App\EasyMinerModule\Presenters
 */
class RuleClipboardPresenter  extends BasePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  TasksFacade $tasksFacade */
  private $tasksFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

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
        if ($task->rulesInRuleClipboardCount>0){
          $result[$task->taskUuid]=array('task_id'=>$task->taskId,'name'=>$task->name,'rules'=>$task->rulesCount,'rule_clipboard_rules'=>$task->rulesInRuleClipboardCount);
        }
      }
    }
    uasort($result,function($a,$b){
      if ($a['task_id']==$b['task_id']){
        return 0;
      }elseif($b['task_id']>$a['task_id']){
        return 1;
      }else{
        return 0;
      }
    });
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
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit,true);
    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=array('text'=>$rule->text,'a'=>$rule->a,'b'=>$rule->b,'c'=>$rule->c,'d'=>$rule->d,'selected'=>($rule->inRuleClipboard?'1':'0'));
      }
    }
    $this->sendJsonResponse(array('task'=>array('rulesCount'=>$this->rulesFacade->getRulesCountByTask($task,true),'IMs'=>$task->getInterestMeasures()),'rules'=>$rulesArr));
  }

  /**
   * Akce pro přidání pravidla do rule clipboard
   * @param int $miner
   * @param string $task
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   */
  public function actionAddRule($miner,$task,$rules){
    $resultRules=$this->changeRulesClipboardState($miner,$task,$rules,true);
    $result=array();
    if (!empty($resultRules)){
      foreach($resultRules as $rule){
        $result[$rule->ruleId]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * @param int $miner
   * @param string $task
   * @param string $returnRules='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   */
  public function actionAddAllRules($miner,$task,$returnRules=''){
    $this->checkMinerAccess($miner);
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));
    //označení všech pravidel patřících do dané úlohy
    $this->rulesFacade->changeAllTaskRulesClipboardState($task,true);
    $this->tasksFacade->checkTaskInRuleClipoard($task);

    $result=array();
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskUuid!=$task->taskUuid){
            throw new InvalidArgumentException;
          }
          if ($ruleTask->miner->minerId!=$miner){
            throw new InvalidArgumentException;
          }
          $result[$rule->ruleId]=$rule->getBasicDataArr();
        }catch (\Exception $e){
          continue;}
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * @param int $miner
   * @param string $task
   * @param string $returnRules='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   */
  public function actionRemoveAllRules($miner,$task,$returnRules=''){
    $this->checkMinerAccess($miner);
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));
    //označení všech pravidel patřících do dané úlohy
    $this->rulesFacade->changeAllTaskRulesClipboardState($task,false);
    $this->tasksFacade->checkTaskInRuleClipoard($task);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Akce pro odebrání pravidla z rule clipboard
   * @param int $miner
   * @param string $task
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   */
  public function actionRemoveRule($miner,$task,$rules){
    $resultRules=$this->changeRulesClipboardState($miner,$task,$rules,false);
    $result=array();
    if (!empty($resultRules)){
      foreach($resultRules as $rule){
        $result[$rule->ruleId]=$rule->getBasicDataArr();
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
    $this->tasksFacade->checkTaskInRuleClipoard($ruleTask);
    return $result;
  }


  /**
   * Akce pro přidání celého obsahu rule clipboard do zvoleného rulesetu
   * @param int $miner
   * @param string $task
   * @param int $ruleset
   * @param string $relation
   * @param string $returnRules ='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   * @throws \Nette\Application\BadRequestException
   */
  public function actionAddRulesToRuleSet($miner,$task,$ruleset,$relation=RuleSetRuleRelation::RELATION_POSITIVE,$returnRules=''){
    //načtení dané úlohy a zkontrolování přístupu k mineru
    $this->checkMinerAccess($miner);
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //přidání pravidel
    $this->ruleSetsFacade->addAllRuleClipboardRulesToRuleSet($task,$ruleSet,$relation);

    $result=array();
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskUuid!=$task->taskUuid){
            throw new InvalidArgumentException;
          }
          if ($ruleTask->miner->minerId!=$miner){
            throw new InvalidArgumentException;
          }
          $result[$rule->ruleId]=$rule->getBasicDataArr();
        }catch (\Exception $e){
          continue;}
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * Akce pro odebrání celého obsahu rule clipboard z konkrétního rulesetu
   * @param int $miner
   * @param string $task
   * @param int $ruleset
   * @param string $relation
   * @param string $returnRules ='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   * @throws \Nette\Application\BadRequestException
   */
  public function actionRemoveRulesFromRuleSet($miner,$task,$ruleset,$returnRules=''){
    //načtení dané úlohy a zkontrolování přístupu k mineru
    $this->checkMinerAccess($miner);
    $task=$this->tasksFacade->findTaskByUuid($miner,$task);
    $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //přidání pravidel
    $this->ruleSetsFacade->removeAllRuleClipboardRulesFromRuleSet($task,$ruleSet);

    $result=array();
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskUuid!=$task->taskUuid){
            throw new InvalidArgumentException;
          }
          if ($ruleTask->miner->minerId!=$miner){
            throw new InvalidArgumentException;
          }
          $result[$rule->ruleId]=$rule->getBasicDataArr();
        }catch (\Exception $e){
          continue;}
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }


  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  /**
   * @param TasksFacade $tasksFacade
   */
  public function injectTasksFacade(TasksFacade $tasksFacade){
    $this->tasksFacade=$tasksFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  #endregion injections
} 