<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class RulesPresenter - presenter for work with individual rules
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RulesPresenter  extends BasePresenter{
  use MinersFacadeTrait;

  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * @var string $mode
   * @persistent
   */
  public $mode='default';


  /**
   * Action for rendering details about a rule
   * @param int $id - task ID
   * @param int $miner
   * @param string $rule
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */

  // https://br-dev.lmcloud.vse.cz/easyminercenter-kopp03/em/rules/rule-details/4499?miner=3122&rule=549049
  public function renderRuleDetails($id=null,$miner,$rule){
    $rule=$this->rulesFacade->findRule($rule);
    //check user privileges
    $task=$rule->task;
    $minerId=$miner;
    $miner=$task->miner;
    if ($miner->minerId!=$minerId || $task->taskId!=$id){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected data!'));
    }
    $this->checkMinerAccess($miner);

    $this->template->rule=$rule;
  }

  #region functions for work with knowledge base
  public function actionGetRules($id){
    //TODO
  }

  // https://br-dev.lmcloud.vse.cz/easyminercenter-kopp03/em/rules/get-rules-xml/4499
  public function actionGetRulesXml($id){
      $rules = $this->rulesFacade->findRulesByTask($id);
      $this->template->rules = $rules;
  }

  public function actionAddRule($id,$rule){
    //TODO
  }

  public function actionRemoveRule($id,$rule){
    //TODO
  }
  #endregion

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