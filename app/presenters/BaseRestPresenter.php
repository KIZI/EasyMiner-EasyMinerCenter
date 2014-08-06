<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 6.8.14
 * Time: 21:19
 */

namespace App\Presenters;


use App\Model\Rdf\Repositories\KnowledgeRepository;
use App\Model\XmlSerializer;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;
use Nette\Http\Response;

abstract class BaseRestPresenter extends BasePresenter{
  /** @var  KnowledgeRepository $knowledgeRepository */
  protected $knowledgeRepository;
  /** @var  XmlSerializer $xmlSerializer */
  protected $xmlSerializer;

  /**
   * @param KnowledgeRepository $knowledgeRepository
   */
  public function injectKnowledgeRepository(KnowledgeRepository $knowledgeRepository){
    $this->knowledgeRepository=$knowledgeRepository;
  }

  public function injectXmlSerializer(XmlSerializer $xmlSerializer){
    $this->xmlSerializer=$xmlSerializer;
  }

  /**
   * Funkce pro odeslání odpovědi informující o nulovém obsahu
   */
  protected function sendNoContentResponse($text=''){
    $response=new Response();
    $response->code=IResponse::S204_NO_CONTENT;
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * @param \SimpleXMLElement|string $simpleXml
   */
  protected function sendXmlResponse($simpleXml){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }
} 