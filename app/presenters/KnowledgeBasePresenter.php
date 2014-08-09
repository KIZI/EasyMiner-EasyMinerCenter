<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class KnowledgeBasePresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených KnowledgeBase
   */
  public function actionList(){
    $knowledgeBases=$this->knowledgeRepository->findKnowledgeBases();
    if (empty($knowledgeBases)){
      $this->sendXmlResponse($this->xmlSerializer->baseKnowledgeBasesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseKnowledgeBasesXml();
    foreach ($knowledgeBases as $knowledgeBase){
      $this->xmlSerializer->blankKnowledgeBaseAsXml($knowledgeBase,$responseXml);
    }
    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce vracející jednu KnowledgeBase ve formátu JSON
   * @param string $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($uri){
    $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($uri);

    if ($knowledgeBase){
      //odešleme daný ruleset
      $this->sendXmlResponse($this->xmlSerializer->knowledgeBaseAsXml($knowledgeBase));
    }else{
      throw new BadRequestException('Requested KnowledgeBase not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení KnowledgeBase na základě zaslaného XML
   * @param string $uri
   * @param string $data = null - pravidlo ve formátu JSON
   * @throws \Nette\Application\BadRequestException
   * @internal param $_POST['data']
   */
  public function actionSave($uri,$data=null){
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('KnowledgeBase data are missing!');
      }
    }

    $xml=$this->xmlUnserializer->prepareKnowledgeBaseXml($data);
    $knowledgeBase=$this->xmlUnserializer->knowledgeBaseFromXml($xml);
    $knowledgeBase->uri=$uri;
    $this->knowledgeRepository->saveRuleSet($knowledgeBase);
    $this->actionGet($knowledgeBase->uri);
  }
} 