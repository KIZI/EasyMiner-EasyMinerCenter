<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;

/**
 * Class BrePresenter - presenter with the functionality for integration of the submodule EasyMiner-BRE
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BrePresenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;

  /** @var MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var RulesFacade $rulesFacade */
  private $rulesFacade;


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

  /**
   * Akce vracející detaily jednoho pravidla
   * @param int $ruleset
   * @param int $rule
   * @throws \Nette\Application\BadRequestException
   * @throws \Exception
   */
  public function actionGetRule($ruleset,$rule){
    $ruleset=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);
    if (!($rulesetRuleRelation=$this->ruleSetsFacade->findRuleSetRuleRelation($ruleset,$rule))){
      //kontrola, jestli je pravidlo v rule setu
      throw new EntityNotFoundException('Rule is not in RuleSet!');
    }
    $rule=$rulesetRuleRelation->rule;

    //TODO
  }

  /**
   * Akce pro uložení upraveného pravidla
   * @param int $ruleset
   * @param int $rule
   * @param string $relation
   * @throws \Nette\Application\BadRequestException
   */
  public function actionSaveRule($ruleset,$rule,$relation=RuleSetRuleRelation::RELATION_POSITIVE){
    $ruleset=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);
    $rule=$this->rulesFacade->findRule($rule);
    if (!empty($rule->task)){
      //pravidlo je součástí úlohy => odebereme ho z rule setu a vytvoříme pravidlo nové
      try{
        $this->ruleSetsFacade->removeRuleFromRuleSet($rule, $ruleset);
      }catch (\Exception $e){
        //chybu ignorujeme - stejně budeme přidávat nové pravidlo
      }
      $rule=null;
    }
    //TODO uložení pravidla

  }

  /**
   * Akce pro odebrání pravidla z rule setu a případné odebrání celého pravidla
   * @param int $ruleset
   * @param int $rule
   * @throws EntityNotFoundException
   * @throws \Nette\Application\BadRequestException
   * @throws \Exception
   */
  public function actionRemoveRule($ruleset, $rule){
    $ruleset=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);
    if (!($rulesetRuleRelation=$this->ruleSetsFacade->findRuleSetRuleRelation($ruleset,$rule))){
      //kontrola, jestli je pravidlo v rule setu
      $this->sendJsonResponse(['state'=>'ok']);
    }

    $rule=$rulesetRuleRelation->rule;
    if (empty($rule->task)){
      //pravidlo není součástí úlohy => zkontrolujeme, jestli je v nějakém rulesetu
      $ruleSetRuleRelations=$rule->ruleSetRuleRelations;
      if (count($ruleSetRuleRelations)<=1){
        //smažeme samotné pravidlo
        $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleset);
        $this->rulesFacade->deleteRule($rule);
      }else{
        $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleset);
      }
    }else{
      $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleset);
    }
    $this->sendJsonResponse(['state'=>'ok']);
  }

  #region injections
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade){
    $this->metasourcesFacade=$metasourcesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  #endregion injections
} 