<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\Utils\Json;

/**
 * Trait ResponsesTrait
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 *
 * @method IResponse getHttpResponse()
 * @method sendResponse(\Nette\Application\IResponse $response)
 */
trait ResponsesTrait {
  /**
   * Funkce pro odeslání odpovědi informující o nulovém obsahu
   * @param string $text=''
   */
  protected function sendNoContentResponse($text=''){
    $response=new Response();
    $response->code=IResponse::S204_NO_CONTENT;
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Funkce pro odeslání odpovědi informující o nulovém obsahu
   * @param string $text=''
   * @param int $code=200
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
  protected function sendXmlResponse($simpleXml,$code=IResponse::S200_OK){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $httpResponse->setCode($code);
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }

  /**
   * Funkce pro odeslání JSON odpovědi
   * @param array|object|string $data
   */
  protected function sendJsonResponse($data,$code=IResponse::S200_OK){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/json','UTF-8');
    $httpResponse->setCode($code);
    $this->sendResponse(new TextResponse((is_string($data)?$data:Json::encode($data))));
  }
}
