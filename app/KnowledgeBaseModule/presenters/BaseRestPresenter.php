<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 6.8.14
 * Time: 21:19
 */

namespace App\KnowledgeBaseModule\Presenters;


use App\Model\Rdf\Repositories\KnowledgeRepository;
use App\Model\Rdf\Serializers\XmlSerializer;
use App\Model\Rdf\Serializers\XmlUnserializer;

abstract class BaseRestPresenter extends \App\Presenters\BaseRestPresenter{
  /** @var  KnowledgeRepository $knowledgeRepository */
  protected $knowledgeRepository;
  /** @var  XmlSerializer $xmlSerializer */
  protected $xmlSerializer;
  /** @var  XmlUnserializer $xmlUnserializer */
  protected $xmlUnserializer;

  /**
   * @param KnowledgeRepository $knowledgeRepository
   */
  public function injectKnowledgeRepository(KnowledgeRepository $knowledgeRepository){
    $this->knowledgeRepository=$knowledgeRepository;
  }

  /**
   * @param XmlSerializer $xmlSerializer
   */
  public function injectXmlSerializer(XmlSerializer $xmlSerializer){
    $this->xmlSerializer=$xmlSerializer;
  }

  public function injectXmlUnserializer(XmlUnserializer $xmlUnserializer){
    $this->xmlUnserializer=$xmlUnserializer;
  }

} 