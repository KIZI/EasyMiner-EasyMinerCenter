<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class RuleClipboardPresenter - presenter pro práci s Rule clipboard v rámci EasyMineru
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class RuleClipboardPresenter  extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;

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
    $miner=$this->findMinerWithCheckAccess($miner);

    $tasks=$miner->tasks;
    $result=array();
    if (!empty($tasks)){
      foreach ($tasks as $task){
        if ($task->rulesInRuleClipboardCount>0){
          $result[$task->taskId]=array('task_id'=>$task->taskId,'name'=>$task->name,'rules'=>$task->rulesCount,'rule_clipboard_rules'=>$task->rulesInRuleClipboardCount,'rules_order'=>strtoupper($task->rulesOrder),'state'=>$task->state,'importState'=>$task->importState);
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
   * @param int $id
   * @param int $miner
   * @param int $offset=0
   * @param int $limit=25
   * @param string $order = ''
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionGetRules($id=null,$miner,$offset=0,$limit=25,$order=''){
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $task=$this->tasksFacade->findTask($id);
    $miner=$task->miner;
    $this->checkMinerAccess($miner);

    if ($order!='' && strtolower($order)!=$task->rulesOrder){
      try{
        $task->setRulesOrder($order);
        $this->tasksFacade->saveTask($task);
      }catch (\Exception $e){
        /*ignore error...*/
        $order=$task->rulesOrder;
      }
    }

    $rules=$this->rulesFacade->findRulesByTask($task,$order,$offset,$limit,true);
    $rulesArr=array();
    if (!empty($rules)){
      foreach ($rules as $rule){
        $rulesArr[$rule->ruleId]=array('text'=>$rule->text,'a'=>$rule->a,'b'=>$rule->b,'c'=>$rule->c,'d'=>$rule->d,'selected'=>($rule->inRuleClipboard?'1':'0'));
      }
    }
    $this->sendJsonResponse(array('task'=>array('rulesCount'=>$this->rulesFacade->getRulesCountByTask($task,true),'IMs'=>$task->getInterestMeasures(),'rulesOrder'=>strtoupper($task->rulesOrder),'state'=>$task->state,'importState'=>$task->importState),'rules'=>$rulesArr));
  }

  /**
   * Akce pro přidání pravidla do rule clipboard
   * @param int $id
   * @param int $miner
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   */
  public function actionAddRule($id,$miner,$rules){
    $resultRules=$this->changeRulesClipboardState($id,$miner,$rules,true);
    $result=array();
    if (!empty($resultRules)){
      foreach($resultRules as $rule){
        $result[$rule->ruleId]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * Akce pro přidání celých výsledků úlohy do rule clipboard
   * @param int $id
   * @param int $miner
   * @param string $returnRules='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   */
  public function actionAddAllRules($id=null,$miner,$returnRules=''){
    $task=$this->tasksFacade->findTask($id);
    $this->checkMinerAccess($task->miner);

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
          if ($ruleTask->taskId!=$task->taskId){
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
   * @param int $id
   * @param int $miner
   * @param string $returnRules='' - IDčka oddělená čárkami, případně jedno ID
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionRemoveAllRules($id=null,$miner,$returnRules=''){
    $task=$this->tasksFacade->findTask($id);
    $this->checkMinerAccess($task->miner);

    $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));//TODO IDčka???
    //označení všech pravidel patřících do dané úlohy
    $this->rulesFacade->changeAllTaskRulesClipboardState($task,false);
    $this->tasksFacade->checkTaskInRuleClipoard($task);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Akce pro odebrání pravidla z rule clipboard
   * @param int $miner
   * @param string $rules - IDčka oddělená čárkami, případně jedno ID
   */
  public function actionRemoveRule($id,$miner,$rules){
    $resultRules=$this->changeRulesClipboardState($id,$miner,$rules,false);
    $result=array();
    if (!empty($resultRules)){
      foreach($resultRules as $rule){
        $result[$rule->ruleId]=$rule->getBasicDataArr();
      }
    }
    $this->sendJsonResponse(array('rules'=>$result));
  }

  /**
   * @param int $id
   * @param int $miner
   * @param int|string $rules
   * @param bool $inRuleClipboard
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   * @return Rule[]
   */
  private function changeRulesClipboardState($id=null,$miner,$rules,$inRuleClipboard){
    $task=$this->tasksFacade->findTask($id);

    $this->checkMinerAccess($task->miner);
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    $result=array();
    if (count($ruleIdsArr)>0){
      foreach ($ruleIdsArr as $ruleId){
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          //TODO optimalizovat kontroly...
          $ruleTask=$rule->task;
          if ($ruleTask->taskId!=$task->taskId){
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
   * @param int $id
   * @param int $miner
   * @param int $ruleset
   * @param string $relation
   * @param string $returnRules ='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   * @throws BadRequestException
   */
  public function actionAddRulesToRuleSet($id=null,$miner,$ruleset,$relation=RuleSetRuleRelation::RELATION_POSITIVE,$returnRules=''){
    //načtení dané úlohy a zkontrolování přístupu k mineru
    $task=$this->tasksFacade->findTask($id);
    $this->checkMinerAccess($task->miner);

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
          if ($ruleTask->taskId!=$task->taskId){
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
   * @param int $id
   * @param int $miner
   * @param int $ruleset
   * @param string $returnRules ='' - IDčka oddělená čárkami, případně jedno ID
   * @throws ForbiddenRequestException
   * @throws BadRequestException
   */
  public function actionRemoveRulesFromRuleSet($id=null,$miner,$ruleset,$returnRules=''){
    //načtení dané úlohy a zkontrolování přístupu k mineru
    $task=$this->tasksFacade->findTask($id);
    $this->checkMinerAccess($task->miner);

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
          if ($ruleTask->taskId!=$task->taskId){
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