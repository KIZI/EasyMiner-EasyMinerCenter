<?php

namespace App\KnowledgeBaseModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/**
 * Class AttributePresenter - presenter pro práci s jednotlivými atributy
 * @package App\Presenters
 */
class AttributePresenter extends BaseRestPresenter{

  /**
   * Akce pro vypsání seznamu atributů ve formátu XML
   * @param string $baseId
   */
  public function actionList($baseId=''){
    $attributes=$this->knowledgeRepository->findAttributes(array('knowledgeBase'=>$baseId));
    if (empty($attributes)){
      $this->sendXmlResponse($this->xmlSerializer->baseAttributesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseAttributesXml();
    foreach ($attributes as $attribute){
      $this->xmlSerializer->blankAttributeAsXml($attribute,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce vracející konkrétní atribut ve formátu XML
   * @param string $baseId
   * @param $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $attribute=$this->knowledgeRepository->findAttribute($uri);

    if ($attribute && $baseId){
      if (@$attribute->knowledgeBase->uri!=$baseId){
        $attribute=null;
      }
    }
    if ($attribute){
      //odešleme daný atribut
      $this->sendXmlResponse($this->xmlSerializer->attributeAsXml($attribute));
    }else{
      throw new BadRequestException('Requested Attribute not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení atributu
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
        throw new BadRequestException('Attributes data are missing!');
      }
    }

    $xml=$this->xmlUnserializer->prepareAttributeXml($data);
    $attribute=$this->xmlUnserializer->attributeFromXml($xml);
    $attribute->uri=$uri;
    if ($baseId){
      //atribut má patřit do konkrétní KnowledgeBase
      $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($baseId);
      $attribute->knowledgeBase=$knowledgeBase;
    }
    $this->knowledgeRepository->saveAttribute($attribute);
    $this->actionGet($baseId,$attribute->uri);
  }


} 