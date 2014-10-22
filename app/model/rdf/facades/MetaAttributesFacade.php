<?php

namespace App\Model\Rdf\Facades;

use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Repositories\FormatsRepository;
use App\Model\Rdf\Repositories\MetaAttributesRepository;
use App\Model\Rdf\Entities\MetaAttribute;


class MetaAttributesFacade {
  /** @var  MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var  FormatsRepository $formatsRepository */
  private $formatsRepository;

  /**
   * @param DatasourceColumn $datasourceColumn
   * @return Format
   */
  public function createFormatFromDatasourceColumn(DatasourceColumn $datasourceColumn){
    $format=new Format();
    //TODO vytvoření formátu metaatributu
  }

  /**
   * Funkce pro aktualizaci formátu na základě hodnot z daného DatasourceColumn
   * @param Format $format
   * @param DatasourceColumn $datasourceColumn
   */
  public function updateFormatFromDatasourceColumn(Format $format, DatasourceColumn $datasourceColumn){
    //TODO rozšíření rozsahu formátu z datasourceColumn
  }

  /**
   * @param string $uri
   * @return \App\Model\Rdf\Entities\MetaAttribute
   */
  public function findMetaAttribute($uri){
    return $this->metaAttributesRepository->findMetaAttribute($uri);
  }

  /**
   * @param string $uri
   * @return \App\Model\Rdf\Entities\Format
   */
  public function findFormat($uri){
    return $this->formatsRepository->findFormat($uri);
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

  /**
   * @param Format $format
   */
  public function saveFormat(Format $format){
    $this->formatsRepository->saveFormat($format);
  }



  public function __construct(MetaAttributesRepository $metaAttributesRepository,FormatsRepository $formatsRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
  }

} 