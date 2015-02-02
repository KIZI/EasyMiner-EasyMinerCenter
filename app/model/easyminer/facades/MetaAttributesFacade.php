<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Format;
use App\Model\EasyMiner\Entities\MetaAttribute;
use App\Model\EasyMiner\Entities\Preprocessing;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\MetaAttributesRepository;
use App\Model\EasyMiner\Repositories\FormatsRepository;
use App\Model\EasyMiner\Repositories\PreprocessingsRepository;

class MetaAttributesFacade {
  /** @var MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var FormatsRepository $formatsRepository */
  private $formatsRepository;
  /** @var PreprocessingsRepository $preprocessingsRepository */
  private $preprocessingsRepository;


  /**
   * @param MetaAttributesRepository $metaAttributesRepository
   * @param FormatsRepository $formatsRepository
   */
  public function __construct(MetaAttributesRepository $metaAttributesRepository, FormatsRepository $formatsRepository, PreprocessingsRepository $preprocessingsRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
    $this->preprocessingsRepository=$preprocessingsRepository;
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
   * @param array $params = array()
   * @param int $offset = null
   * @param int $limit = null
   * @return Format[]|null
   */
  public function findFormats($params=array(),$offset=null,$limit=null){
    if (!empty($params['user'])){
      $user=$params['user'];
      if ($user instanceof User){
        $paramsArr[]=array('user_id=%i OR shared=1',$user->userId);
      }else{
        $paramsArr[]=array('user_id=%i OR shared=1',$user);
      }
      unset($params['user']);
    }
    if (!empty($params['metaAttribute'])){
      $metaAttribute=$params['metaAttribute'];
      if ($metaAttribute instanceof MetaAttribute){
        $paramsArr['meta_attribute_id']=$metaAttribute->metaAttributeId;
      }else{
        $paramsArr['meta_attribute_id']=$metaAttribute;
      }
      unset($params['metaAttribute']);
    }
    return $this->formatsRepository->findAllBy($params,$offset,$limit);
  }

  /**
   * @param array $params = array()
   * @param int $offset = null
   * @param int $limit = null
   * @return Preprocessing[]|null
   */
  public function findPreprocessings($params=array(),$offset=null,$limit=null){
    if (!empty($params['user'])){
      $user=$params['user'];
      if ($user instanceof User){
        $paramsArr[]=array('user_id=%i OR shared=1',$user->userId);
      }else{
        $paramsArr[]=array('user_id=%i OR shared=1',$user);
      }
      unset($params['user']);
    }
    if (!empty($params['format'])){
      $format=$params['format'];
      if ($format instanceof Format){
        $paramsArr['format_id']=$format->formatId;
      }else{
        $paramsArr['format_id']=$format;
      }
      unset($params['format']);
    }
    return $this->preprocessingsRepository->findAllBy($params,$offset,$limit);
  }

  /**
   * @param string $name
   * @return MetaAttribute
   * @throws \Exception
   */
  public function findMetaAttributeByName($name){
    return $this->metaAttributesRepository->findBy(array('name'=>$name));
  }

  /**
   * @param string $name
   * @return Format
   * @throws \Exception
   */
  public function findFormatByName($name){
    return $this->formatsRepository->findBy(array('name'=>$name));
  }

  /**
   * @param MetaAttribute|int $metaAttribute
   * @param User|int $user
   * @return Format[]
   */
  public function findFormatsForUser($metaAttribute,$user){
    return $this->findFormats(array('metaAttribute'=>$metaAttribute,'user'=>$user,'order'=>'name'));
  }

  /**
   * @param Format|int $format
   * @param User|int $user
   * @return Preprocessing[]
   */
  public function findPreprocessingsForUser($format,$user){
    return $this->findPreprocessings(array('format'=>$format,'user'=>$user,'order'=>'name'));
  }

}