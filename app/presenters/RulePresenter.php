<?php

namespace App\Presenters;


use App\Model\XmlSerializer;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class RulePresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených pravidel
   * @param string $baseId = ''
   */
  public function actionList($baseId=''){
    $rules=$this->knowledgeRepository->findRules();//TODO filtrování na základě zadaného baseId
    if (empty($metaattributes)){
      $this->sendXmlResponse(XmlSerializer::baseRulesXml());
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
   * @param string $data - pravidlo ve formátu JSON
   */
  public function actionSave($baseId='',$uri,$data){
    //TODO
  }
} 