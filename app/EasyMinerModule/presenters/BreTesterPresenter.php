<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\BreTestUser;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\BreTestsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\BreRuleSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\BreRuleUnserializer;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScorerDriverFactory;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;

/**
 * Class BreTesterPresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BreTesterPresenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;

  /** @var BreTestsFacade $breTestsFacade */
  private $breTestsFacade;
  /** @var MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var ScorerDriverFactory $scorerDriverFactory */
  private $scorerDriverFactory;


  /**
   * @param string $testKey
   * @throws BadRequestException
   */
  public function renderTest($testKey){
    try{
      $breTest=$this->breTestsFacade->findBreTestByKey($testKey);
    }catch (\Exception $e){
      throw new BadRequestException();
    }

    $this->template->breTest=$breTest;
  }

  /**
   * @param string $testKey
   * @throws BadRequestException
   */
  public function actionNewUser($testKey){
    try{
      $breTest=$this->breTestsFacade->findBreTestByKey($testKey);
    }catch (\Exception $e){
      throw new BadRequestException();
    }
    //create new user and redirect him to editor
    $breTestUser=$this->breTestsFacade->createNewBreTestUser($breTest);
    $this->redirect('default',['testUserKey'=>$breTestUser->testKey]);
  }

  /**
   * Action for display of EasyMiner-BRE
   * @param string $testUserKey
   * @throws \Exception
   */
  public function renderDefault($testUserKey){
    try{
      $breTestUser=$this->breTestsFacade->findBreTestUserByKey($testUserKey);
    }catch (\Exception $e){
      throw new BadRequestException();
    }

    $this->template->javascriptFiles=BREIntegration::$javascriptFiles;
    $this->template->cssFiles=BREIntegration::$cssFiles;
    $this->template->content=BREIntegration::getContent();
    $this->template->moduleName=BREIntegration::MODULE_NAME;
    $this->template->ruleSet=$breTestUser->ruleSet;
    $this->template->miner=$breTestUser->breTest->miner;
  }

  /**
   * Akce vracející seznam atributů dle mineru
   * @param int $miner
   * @throws \Nette\Application\BadRequestException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function actionGetAttributesByMiner($miner){
    $miner=$this->findMinerWithCheckAccess($miner);//FIXME
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

    $binsArr=[];
    $ppValues=$this->metasourcesFacade->getAttributePpValues($attribute,0,intval($valuesLimit));
    if (!empty($ppValues)){
      foreach ($ppValues as $ppValue){
        $binsArr[]=$ppValue->value;
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
      'bins'=>$binsArr
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
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);//FIXME
    if (!($rulesetRuleRelation=$this->ruleSetsFacade->findRuleSetRuleRelation($ruleset,$rule))){
      //kontrola, jestli je pravidlo v rule setu
      throw new EntityNotFoundException('Rule is not in RuleSet!');
    }
    $rule=$rulesetRuleRelation->rule;
    $breRuleSerializer=new BreRuleSerializer();
    $breRuleSerializer->serializeRule($rule);
    $this->sendXmlResponse($breRuleSerializer->getXml());
  }

  /**
   * Akce pro uložení upraveného pravidla
   * @param int $ruleset
   * @param int $rule
   * @param string $relation
   * @throws \Nette\Application\BadRequestException
   * @throws InvalidArgumentException
   * @throws \Exception
   */
  public function actionSaveRule($ruleset,$rule,$relation=RuleSetRuleRelation::RELATION_POSITIVE){
    #region nalezení pravidla, ošetření jeho vztahu k rule setu
    $ruleset=$this->ruleSetsFacade->findRuleSet($ruleset);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);//FIXME
    try{
      $existingRule=$this->rulesFacade->findRule($rule);
    }catch (\Exception $e){/*pravidlo nebylo nalezeno*/}

    if (!empty($existingRule->task)){
      //pravidlo je součástí úlohy => odebereme ho z rule setu a vytvoříme pravidlo nové
      try{
        $this->ruleSetsFacade->removeRuleFromRuleSet($existingRule, $ruleset);
      }catch (\Exception $e){
        //chybu ignorujeme - stejně budeme přidávat nové pravidlo
      }
      $rule=new Rule();
      $rule->a=$existingRule->a;
      $rule->b=$existingRule->b;
      $rule->c=$existingRule->c;
      $rule->d=$existingRule->d;
      $rule->confidence=$existingRule->confidence;
      $rule->support=$existingRule->support;
      $rule->lift=$existingRule->lift;
    }elseif(!empty($existingRule)){
      $rule=$existingRule;
    }
    #endregion nalezení pravidla, ošetření jeho vztahu k rule setu

    #region naparsování XML zápisu pravidla
    try{
      $ruleXml=simplexml_load_string($data=$this->getHttpRequest()->getRawBody());
      if (!($ruleXml instanceof  \SimpleXMLElement)){
        throw new \Exception();
      }
    }catch (\Exception $e){
      throw new InvalidArgumentException('Rule XML cannot be parsed!');
    }
    $breRuleUnserializer=new BreRuleUnserializer($this->rulesFacade, $this->metasourcesFacade, $this->metaAttributesFacade);
    $rule=$breRuleUnserializer->unserialize($ruleXml,($rule instanceof Rule?$rule:null));
    #endregion naparsování XML zápisu pravidla

    #region kontrola výsledku a odeslání odpovědi
    if ($rule instanceof Rule){
      $this->ruleSetsFacade->addRuleToRuleSet($rule,$ruleset,RuleSetRuleRelation::RELATION_POSITIVE);
    }else{
      throw new \Exception('Rule was not saved.');
    }
    $breRuleSerializer=new BreRuleSerializer();
    $breRuleSerializer->serializeRule($rule);
    $this->sendXmlResponse($breRuleSerializer->getXml());
    #endregion kontrola výsledku a odeslání odpovědi
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
    $this->ruleSetsFacade->checkRuleSetAccess($ruleset,$this->user->id);//FIXME
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

  /**
   * @param string $testUserKey
   * @throws \Nette\Application\AbortException
   * @throws \Nette\Application\BadRequestException
   */
  public function renderScorer($testUserKey){
    try{
      /** @var BreTestUser $breTestUser */
      $breTestUser=$this->breTestsFacade->findBreTestUserByKey($testUserKey);
    }catch (\Exception $e){
      throw new BadRequestException();
    }

    //run scorer and show results
    /** @var IScorerDriver $scorerDriver */
    $scorerDriver=$this->scorerDriverFactory->getDefaultScorerInstance();

    $this->layout='iframe';
    $this->template->layout='iframe';
    $this->template->ruleSet=$breTestUser->ruleSet;
    $this->template->datasource=$breTestUser->breTest->datasource;
    $this->template->scoringResult=$scorerDriver->evaluateRuleSet($breTestUser->ruleSet,$breTestUser->breTest->datasource);
  }

  #region injections
  /**
   * @param BreTestsFacade $breTestsFacade
   */
  public function injectBreTestsFacade(BreTestsFacade $breTestsFacade){
    $this->breTestsFacade=$breTestsFacade;
  }
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
  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  /**
   * @param ScorerDriverFactory $scorerDriverFactory
   */
  public function injectScorerDriverFactory(ScorerDriverFactory $scorerDriverFactory){
    $this->scorerDriverFactory=$scorerDriverFactory;
  }
  #endregion injections
} 