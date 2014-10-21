<?php

namespace App\Model\Rdf\Facades;

use App\Model\Rdf\Repositories\MetaAttributesRepository;
use App\Model\Rdf\Entities\MetaAttribute;


class MetaAttributesFacade {
  /** @var  MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;

  /**
   * @param string $uri
   * @return \App\Model\Rdf\Entities\MetaAttribute
   */
  public function findMetaAttribute($uri){
    return $this->metaAttributesRepository->findMetaAttribute($uri);
  }

  /**
   * @param array $params = array()
   * @param int $limit = -1
   * @param int $offset = -1
   * @return MetaAttribute[]
   */
  public function findMetaAttributes($params=array(),$limit=-1,$offset=-1){
    return $this->metaAttributesRepository->findMetaAttributes($params,$limit,$offset);
  }

  /**
   * @param MetaAttribute $metaAttribute
   */
  public function saveMetaAttribute(MetaAttribute $metaAttribute){
    $this->metaAttributesRepository->saveMetaAttribute($metaAttribute);
  }



  public function __construct(MetaAttributesRepository $metaAttributesRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
  }

} 