<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Entities\Rule;
use App\Model\EasyMiner\Facades\RulesFacade;
use Nette\InvalidArgumentException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class RulesPresenter - presenter pro práci s jednotlivými pravidly
 * @package App\EasyMinerModule\Presenters
 */
class RulesPresenter  extends BasePresenter{
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * @var string $mode
   * @persistent
   */
  public $mode='default';


  /**
   * Akce vracející pravidla pro vykreslení v easymineru
   * @param int $miner
   * @param string $task
   * @param string $rule
   * @throws ForbiddenRequestException
   */
  public function renderRuleDetails($miner,$task,$rule){
    $rule=$this->rulesFacade->findRule($rule);
    $taskUUID=$task;
    $task=$rule->task;
    $minerId=$miner;
    $miner=$task->miner;
    if ($task->taskUuid!=$taskUUID || $miner->minerId!=$minerId){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected data!'));
    }
    $this->checkMinerAccess($miner);
    $this->template->rule=$rule;
  }



  protected function beforeRender(){
    if ($this->mode=='component' || $this->mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout='iframe';
    }
    parent::beforeRender();
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