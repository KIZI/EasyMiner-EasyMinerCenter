<?php

namespace App\Presenters;
use App\Model\XmlSerializer;
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
    $metaattributes=$this->knowledgeRepository->findMetaattributes();//TODO filtrování na základě zadaného baseId
    if (empty($metaattributes)){
      $this->sendXmlResponse(XmlSerializer::baseMetaAttributesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseMetaAttributesXml();
    foreach ($metaattributes as $metaattribute){
      $this->xmlSerializer->blankMetaAttributeAsXml($metaattribute,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  public function actionListWithFormats($baseId=''){
    $metaattributes=$this->knowledgeRepository->findMetaattributes();//TODO filtrování na základě zadaného baseId
    if (empty($metaattributes)){
      $this->sendXmlResponse($this->xmlSerializer->baseMetaAttributesXml());
      return;
    }

    $responseXml=$this->xmlSerializer->baseMetaAttributesXml();
    foreach ($metaattributes as $metaattribute){
      $this->xmlSerializer->metaAttributeWithBlankFormatsAsXml($metaattribute,$responseXml);
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
    $metaattribute=$this->knowledgeRepository->findMetaattribute($uri);
    if ($metaattribute){
      //odešleme daný metaatribut
      $this->sendXmlResponse($this->xmlSerializer->metaattributeAsXml($metaattribute));
    }else{
      throw new BadRequestException('Requested MetaAttribute not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení metaatributu
   * @param string $baseId
   * @param string $uri
   * @param string $data
   */
  public function actionSave($baseId='',$uri,$data){
    //TODO
  }


} 