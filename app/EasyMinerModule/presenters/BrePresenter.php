<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;

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
  /** @var MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;


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
    $this->minersFacade->checkMinerMetasource($miner);
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

  /**
   * Akce vracející detaily jednoho atributu
   * @param int $attribute
   * @param int $valuesLimit=1000
   * @throws \Nette\Application\BadRequestException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function actionGetAttribute($attribute,$valuesLimit=1000){
    $attribute=$this->metasourcesFacade->findAttribute($attribute);
    $preprocessing=$attribute->preprocessing;
    $format=$preprocessing->format;

    $valuesArr=[];
    $ppValues=$this->metasourcesFacade->getAttributePpValues($attribute,0,intval($valuesLimit));
    if (!empty($ppValues)){
      foreach ($ppValues as $ppValue){
        $valuesArr[]=$ppValue->value;
      }
    }

    $result=[
      'id'=>$attribute->attributeId,
      'name'=>$attribute->name,
      'preprocessing'=>$preprocessing->preprocessingId,
      'format'=>[
        'id'=>$format->formatId,
        'name'=>$format->name,
        'dataType'=>$format->dataType
      ],
      'bins'=>$valuesArr
      //tady možná doplnit ještě range?
    ];
    $this->sendJsonResponse($result);
  }


  #region injections
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade){
    $this->metasourcesFacade=$metasourcesFacade;
  }
  #endregion injections
} 