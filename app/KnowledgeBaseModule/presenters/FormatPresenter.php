<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 6.8.14
 * Time: 21:00
 */

namespace App\KnowledgeBaseModule\Presenters;


use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class FormatPresenter extends BaseRestPresenter{

  /**
   * Akce vracející jedno pravidlo ve formátu JSON
   * @param string $baseId = ''
   * @param string $uri
   * @throws BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $format=$this->knowledgeRepository->findFormat($uri);

    if ($format && $baseId){
      //zkontrolujeme, jestli jde o formát patřící do metaatributu, který patří do zadané KnowledgeBase
      $metaAttribute=$format->metaAttribute;
      if ($metaAttribute->uri!=$baseId){
        $format=null;
      }
    }

    if ($format){
      //odešleme daný formát zabalený do základních info o metaatributu
      $this->sendXmlResponse($this->xmlSerializer->formatInBlankMetaAttributeAsXml($format));
    }else{
      throw new BadRequestException('Requested MetaAttribute not found.',IResponse::S404_NOT_FOUND);
    }
  }


} 