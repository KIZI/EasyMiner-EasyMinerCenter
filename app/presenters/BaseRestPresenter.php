<?php

namespace EasyMinerCenter\Presenters;

use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\Utils\Json;

class BaseRestPresenter extends BasePresenter{
  /**
   * Funkce pro odeslání odpovědi informující o nulovém obsahu
   */
  protected function sendNoContentResponse($text=''){
    $response=new Response();
    $response->code=IResponse::S204_NO_CONTENT;
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Funkce pro odeslání odpovědi informující o nulovém obsahu
   */
  protected function sendTextResponse($text='',$code=IResponse::S200_OK){
    $response=new Response();
    $response->code=$code;
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('text/plain','UTF-8');
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Funkce pro odeslání XML odpovědi
   * @param \SimpleXMLElement|string $simpleXml
   */
  protected function sendXmlResponse($simpleXml){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }

  /**
   * Funkce pro odeslání JSON odpovědi
   * @param array|object|string $data
   */
  protected function sendJsonResponse($data){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/json','UTF-8');
    $this->sendResponse(new TextResponse((is_string($data)?$data:Json::encode($data))));
  }
} 