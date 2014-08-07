<?php

namespace App\Model;
use App\Model\Rdf\Repositories\KnowledgeRepository;
use Nette\Object;

/**
 * Class XmlUnserializer - třída pro unserializaci entit z XML do podoby objektů
 * Třída je součástí Facade pro ukládání entit v závislosti na znalostní bázi
 * @package App\Model
 */
class XmlUnserializer extends Object{
  /** @var  KnowledgeRepository $knowledgeRepository */
  protected $knowledgeRepository;

  public function __construct(KnowledgeRepository $knowledgeRepository){
    $this->knowledgeRepository=$knowledgeRepository;
  }

} 