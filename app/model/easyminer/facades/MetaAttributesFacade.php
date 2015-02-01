<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Format;
use App\Model\EasyMiner\Entities\MetaAttribute;
use App\Model\EasyMiner\Entities\Metasource;
use App\Model\EasyMiner\Repositories\MetaAttributesRepository;
use App\Model\EasyMiner\Repositories\FormatsRepository;

class MetaAttributesFacade {
  /** @var MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var FormatsRepository $formatsRepository */
  private $formatsRepository;


  /**
   * @param MetaAttributesRepository $metaAttributesRepository
   * @param FormatsRepository $formatsRepository
   */
  public function __construct(MetaAttributesRepository $metaAttributesRepository, FormatsRepository $formatsRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
  }

  /**
   * @param int $id
   * @return MetaAttribute
   */
  public function findMetaAttribute($id) {
    return $this->metaAttributesRepository->find($id);
  }

  /**
   * @param MetaAttribute $metaAttribute
   * @return bool
   */
  public function saveMetaAttribute(MetaAttribute &$metaAttribute) {
    $result = $this->metaAttributesRepository->persist($metaAttribute);
    return $result;
  }


  /**
   * @param MetaAttribute|int $metaAttribute
   * @return int
   */
  public function deleteMetaAttribute($metaAttribute){
    if (!($metaAttribute instanceof MetaAttribute)){
      $metaAttribute=$this->findMetaAttribute($metaAttribute);
    }
    return $this->metaAttributesRepository->delete($metaAttribute);
  }

  /**
   * @param DatasourceColumn $datasourceColumn
   * @param string $formatType = values - values|interval (info o tom, jakým způsobem mají být zachyceny číselné hodnoty)
   * @return Format
   */
  public function createFormatFromDatasourceColumn(DatasourceColumn $datasourceColumn,$formatType='values'){
    $format=new Format();
    $format->dataType=$formatType;
    //TODO vytvoření formátu metaatributu

    return $format;
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
   * @param int $id
   * @return Format
   */
  public function findFormat($id) {
    return $this->formatsRepository->find($id);
  }

  /**
   * @param Format $format
   * @return bool
   */
  public function saveFormat(Format &$format) {
    $result = $this->formatsRepository->persist($format);
    return $result;
  }

  /**
   * @param Format|int $format
   * @return int
   */
  public function deleteFormat($format){
    if (!($format instanceof Format)){
      $format=$this->findFormat($format);
    }
    return $this->formatsRepository->delete($format);
  }

  /**
   * @param array $params = array()
   * @param int $offset = null
   * @param int $limit = null
   * @return MetaAttribute[]|null
   */
  public function findMetaAttributes($params=array(),$offset=null,$limit=null){
    $paramsArr=array();
    /* TODO parametry...
     if (!empty($params['user'])){
      $user=$params['user'];
      if ($user instanceof User){
        $paramsArr['user_id']=$user->userId;
      }else{
        $paramsArr['user_id']=$user;
      }
    }*/
    return $this->metaAttributesRepository->findAllBy($params,$offset,$limit);
  }

  /**
   * @param string $name
   * @return MetaAttribute
   * @throws \Exception
   */
  public function findMetaAttributeByName($name){
    return $this->metaAttributesRepository->findBy(array('name'=>$name));
  }

}