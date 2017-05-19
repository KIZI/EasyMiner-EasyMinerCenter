<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class MinersPresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MinersPresenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;

  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

  /**
   * Akce pro přiřazení rule setu ke zvolenému mineru
   * Action for setting up a selected rule set to selected miner
   * @param int $miner
   * @param int $ruleSet
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionSetRuleSet($miner, $ruleSet){
    $miner=$this->findMinerWithCheckAccess($miner);
    $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleSet);
    $miner->ruleSet=$ruleSet;
    $this->minersFacade->saveMiner($miner);
    $this->sendJsonResponse(['state'=>'ok','miner'=>$miner->minerId,'ruleset_id'=>$ruleSet->ruleSetId]);
  }

  /**
   * Action for saving of miner configuration
   * @param int $miner
   * @param string $property
   * @param string $value
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionSetConfig($miner,$property,$value){
    $miner=$this->findMinerWithCheckAccess($miner);
    $minerConfig=$miner->getExternalConfig();
    $minerConfig[$property]=$value;
    $miner->setExternalConfig($minerConfig);
    $this->minersFacade->saveMiner($miner);
    $this->actionGetConfig($miner->minerId);
  }

  /**
   * Action returning miner configuration as JSON response
   * @param int $miner
   * @param string $property=""
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function actionGetConfig($miner, $property=""){
    $miner=$this->findMinerWithCheckAccess($miner);
    $config=$miner->getExternalConfig();
    if ($property!=''){
      if (!empty($config[$property])){
        $config=$config[$property];
      }else{
        $config="";
      }
    }
    $this->sendJsonResponse($config);
  }

  #region injections
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  #endregion injections
}