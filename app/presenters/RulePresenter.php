<?php

namespace App\Presenters;


use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class RulePresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených pravidel
   * @param string $baseId = ''
   * @param string $ruleset=''
   */
  public function actionList($baseId='',$ruleset=''){
    if ($ruleset){
      //pokud chceme seznam pravidel z konkrétního rulesetu, necháme to vyřídit RuleSetPresenter
      $this->redirect('RuleSet:rules',array('baseId'=>$baseId,'uri'=>$ruleset));
      return;
    }
    $rules=$this->knowledgeRepository->findRules(array('knowledgeBase'=>$baseId));
    if (empty($rules)){
      $this->sendXmlResponse($this->xmlSerializer->baseRulesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseRulesXml();
    foreach ($rules as $rule){
      $this->xmlSerializer->blankRuleAsXml($rule,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce vracející jedno pravidlo ve formátu JSON
   * @param string $baseId = ''
   * @param string $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $rule=$this->knowledgeRepository->findRule($uri);

    if ($rule && $baseId){
      //zkontrolujeme, jestli dané pravidlo patří do zadané KnowledgeBase
      if (@$rule->knowledgeBase->uri!=$uri){
        $rule=null;
      }
    }

    if ($rule){
      //odešleme dané pravidlo
      $this->sendXmlResponse($this->xmlSerializer->ruleAsXml($rule));
    }else{
      throw new BadRequestException('Requested Rule not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení pravidla
   * @param string $baseId = ''
   * @param string $uri
   * @param string $data = null - pravidlo ve formátu JSON
   * @throws \Nette\Application\BadRequestException
   * @internal param $_POST['data']
   */
  public function actionSave($baseId='',$uri,$data=null){
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('Preprocessing data are missing!');
      }
    }

    $xml=$this->xmlUnserializer->prepareRulesXml($data);
    $rule=$this->xmlUnserializer->ruleFromXml($xml);
    $rule->uri=$uri;
    if ($baseId){
      //pravidlo má patřit do konkrétní KnowledgeBase
      $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($baseId);
      $rule->knowledgeBase=$knowledgeBase;
    }
    $this->knowledgeRepository->saveRule($rule);
    $this->actionGet($baseId,$rule->uri);
  }
} 