<?php

namespace App\Presenters;


use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class RulePresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených pravidel
   * @param string $baseId = ''
   */
  public function actionList($baseId=''){
    $rules=$this->knowledgeRepository->findRules();//TODO filtrování na základě zadaného baseId
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
    $rule=$this->knowledgeRepository->findRule($uri);//TODO vyřešení baseId
    if ($rule){
      //odešleme daný metaatribut
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
  public function actionSave($baseId='',$uri,$data=null){//TODO $baseId
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('Preprocessing data are missing!');
      }
    }


    $xml=$this->xmlUnserializer->prepareRulesXml($data);
    $rule=$this->xmlUnserializer->ruleFromXml($xml);
    $rule->uri=$uri;
    $this->knowledgeRepository->saveRule($rule);
    $this->actionGet($baseId,$rule->uri);
  }
} 