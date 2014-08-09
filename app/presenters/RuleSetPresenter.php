<?php

namespace App\Presenters;


use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class RuleSetPresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených pravidel
   * @param string $baseId = ''
   */
  public function actionList($baseId=''){
    $ruleSets=$this->knowledgeRepository->findRuleSets(array('knowledgeBase'=>$baseId));
    if (empty($ruleSets)){
      $this->sendXmlResponse($this->xmlSerializer->baseRuleSetsXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseRuleSetsXml();
    foreach ($ruleSets as $ruleSet){
      $this->xmlSerializer->blankRuleSetAsXml($ruleSet,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce pro vypsání seznamu pravidel zahrnutých do daného RuleSetu
   * @param string $baseId=''
   * @param string $uri
   */
  public function actionRules($baseId='',$uri){
    //TODO
  }

  /**
   * Akce vracející jeden RuleSet ve formátu JSON
   * @param string $baseId = ''
   * @param string $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $ruleSet=$this->knowledgeRepository->findRuleSet($uri);
    if ($baseId && $ruleSet){
      //zkontrolujeme, jestli dané pravidlo patří
      if (@$ruleSet->knowledgeBase->uri!=$baseId){
        $ruleSet=null;
      }
    }
    if ($ruleSet){
      //odešleme daný metaatribut
      $this->sendXmlResponse($this->xmlSerializer->ruleSetAsXml($ruleSet));
    }else{
      throw new BadRequestException('Requested RuleSet not found.',IResponse::S404_NOT_FOUND);
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

    $xml=$this->xmlUnserializer->prepareRuleSetXml($data);
    $rule=$this->xmlUnserializer->ruleSetFromXml($xml);
    $rule->uri=$uri;
    if ($baseId){
      //pravidlo má patřit do konkrétní KnowledgeBase
      $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($baseId);
      $rule->knowledgeBase=$knowledgeBase;
    }
    $this->knowledgeRepository->saveRuleSet($rule);
    $this->actionGet($baseId,$rule->uri);
  }
} 