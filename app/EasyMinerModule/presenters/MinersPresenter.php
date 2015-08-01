<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 31. 7. 2015
 * Time: 12:14
 */

namespace App\EasyMinerModule\Presenters;


use App\Model\EasyMiner\Facades\RuleSetsFacade;
use App\Presenters\BaseRestPresenter;
use Nette\Application\ForbiddenRequestException;

class MinersPresenter extends BasePresenter{

  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

  /**
   * Akce pro přiřazení rule setu ke zvolenému mineru
   * @param int $miner
   * @param int $ruleSet
   * @throws ForbiddenRequestException
   * @throws \Exception
   */
  public function actionSetRuleSet($miner, $ruleSet){
    $miner=$this->minersFacade->findMiner($miner);
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
    $ruleSet=$this->ruleSetsFacade->findRuleSet($ruleSet);
    $miner->ruleSet=$ruleSet;
    $this->minersFacade->saveMiner($miner);
    $this->sendJsonResponse(['state'=>'ok','miner'=>$miner->minerId,'ruleset_id'=>$ruleSet->ruleSetId]);
  }

  /**
   * Funkce pro uložení nastavení konkrétního mineru
   * @param int $miner
   * @param string $property
   * @param string $value
   * @throws ForbiddenRequestException
   */
  public function actionSetConfig($miner,$property,$value){
    $miner=$this->minersFacade->findMiner($miner);
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
    $minerConfig=$miner->getExternalConfig();
    $minerConfig[$property]=$value;
    $miner->setExternalConfig($minerConfig);
    $this->minersFacade->saveMiner($miner);
    $this->actionGetConfig($miner->minerId);
  }

  /**
   * Funkce vracející detaily nastavení zvoleného mineru
   * @param int $miner
   * @param string $property=""
   * @throws ForbiddenRequestException
   */
  public function actionGetConfig($miner, $property=""){
    $miner=$this->minersFacade->findMiner($miner);
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
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