<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use Nette\Utils\Json;

/**
 * Class BrePresenter - presenter with the functionality for integration of the submodule EasyMiner-BRE
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BrePresenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;

  /** @var RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;


  /**
   * Action for display of EasyMiner-BRE
   * @param int $ruleset
   * @param int $miner
   * @throws \Exception
   */
  public function renderDefault($ruleset,$miner){
    $this->template->javascriptFiles=BREIntegration::$javascriptFiles;
    $this->template->cssFiles=BREIntegration::$cssFiles;
    $this->template->content=BREIntegration::getContent();
    $this->template->moduleName=BREIntegration::MODULE_NAME;
    $this->template->ruleSet=$this->ruleSetsFacade->findRuleSet($ruleset);//TODO ošetření chyby
    $this->template->miner=$this->minersFacade->findMiner($miner);
  }

  /**
   * Akce vracející seznam atributů dle mineru
   * @param int $miner
   * @throws \Nette\Application\BadRequestException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function actionGetAttributesByMiner($miner){
    $miner=$this->findMinerWithCheckAccess($miner);
    $metasource=$miner->metasource;
    $attributesArr=$metasource->getAttributesArr();
    $result=[];
    if (!empty($attributesArr)){
      foreach ($attributesArr as $attribute){
        $result[$attribute->attributeId]=['id'=>$attribute->attributeId,'name'=>$attribute->name];
      }
    }
    $this->sendJsonResponse($result);
  }

  public function actionGetAttribute($attributeId){
    //TODO

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