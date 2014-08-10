<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/**
 * Class MetaAttributePresenter - presenter pro práci s jednotlivými metaatributy
 * @package App\Presenters
 */
class MetaAttributePresenter extends BaseRestPresenter{

  /**
   * Akce pro vypsání seznamu metaatributů ve formátu XML
   * @param string $baseId
   */
  public function actionList($baseId=''){
    $metaAttributes=$this->knowledgeRepository->findMetaAttributes(array('knowledgeBase'=>$baseId));
    if (empty($metaAttributes)){
      $this->sendXmlResponse($this->xmlSerializer->baseMetaAttributesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseMetaAttributesXml();
    foreach ($metaAttributes as $metaAttribute){
      $this->xmlSerializer->blankMetaAttributeAsXml($metaAttribute,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  public function actionListWithFormats($baseId=''){
    $metaAttributes=$this->knowledgeRepository->findMetaAttributes(array('knowledgeBase'=>$baseId));
    if (empty($metaAttributes)){
      $this->sendXmlResponse($this->xmlSerializer->baseMetaAttributesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseMetaAttributesXml();
    foreach ($metaAttributes as $metaAttribute){
      $this->xmlSerializer->metaAttributeWithBlankFormatsAsXml($metaAttribute,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce vracející konkrétní metaatribut ve formátu XML
   * @param string $baseId
   * @param $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $metaAttribute=$this->knowledgeRepository->findMetaAttribute($uri);

    if ($metaAttribute && $baseId){
      if (@$metaAttribute->knowledgeBase->uri!=$baseId){
        $metaAttribute=null;
      }
    }
    if ($metaAttribute){
      //odešleme daný metaatribut
      $this->sendXmlResponse($this->xmlSerializer->metaAttributeAsXml($metaAttribute));
    }else{
      throw new BadRequestException('Requested MetaAttribute not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení metaatributu
   * @param string $baseId
   * @param string $uri
   * @param null|string $data
   * @throws \Nette\Application\BadRequestException
   * @internal param $_POST['data']
   */
  public function actionSave($baseId='',$uri='',$data=null){
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('MetaAttributes data are missing!');
      }
    }

    $xml=$this->xmlUnserializer->prepareMetaAttributeXml($data);
    $metaAttribute=$this->xmlUnserializer->metaAttributeFromXml($xml);
    $metaAttribute->uri=$uri;
    if ($baseId){
      //metaatribut má patřit do konkrétní KnowledgeBase
      $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($baseId);
      $metaAttribute->knowledgeBase=$knowledgeBase;
    }
    $this->knowledgeRepository->saveMetaattribute($metaAttribute);
    $this->actionGet($baseId,$metaAttribute->uri);
  }


} 