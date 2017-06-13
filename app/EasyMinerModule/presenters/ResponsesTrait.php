<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\Utils\Json;

/**
 * Trait ResponsesTrait
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @method IResponse getHttpResponse()
 * @method sendResponse(\Nette\Application\IResponse $response)
 */
trait ResponsesTrait {
  /**
   * Method for sending of no content response
   * @param string $text=''
   */
  protected function sendNoContentResponse($text=''){
    $response=new Response();
    $response->code=IResponse::S204_NO_CONTENT;
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Method for sending of plain text response (with configurable response code, default 200)
   * @param string $text=''
   * @param int $code=200 - response code
   */
  protected function sendTextResponse($text='',$code=IResponse::S200_OK){
    $response=new Response();
    $response->code=$code;
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('text/plain','UTF-8');
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Method for sending of XML response (with configurable response code, default 200)
   * @param \SimpleXMLElement|string $simpleXml
   * @param int $code=200 - response code
   */
  protected function sendXmlResponse($simpleXml,$code=IResponse::S200_OK){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $httpResponse->setCode($code);
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }

  /**
   * Method for sending of JSON response (with configurable response code, default 200)
   * @param array|object|string $data
   * @param int $code=200 - response code
   */
  protected function sendJsonResponse($data,$code=IResponse::S200_OK){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/json','UTF-8');
    $httpResponse->setCode($code);
    $this->sendResponse(new TextResponse((is_string($data)?$data:Json::encode($data))));
  }
}
