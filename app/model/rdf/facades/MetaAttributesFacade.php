<?php

namespace App\Model\Rdf\Facades;

use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Preprocessing;
use App\Model\Rdf\Repositories\FormatsRepository;
use App\Model\Rdf\Repositories\MetaAttributesRepository;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Repositories\PreprocessingsRepository;


class MetaAttributesFacade {
  /** @var  MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var  FormatsRepository $formatsRepository */
  private $formatsRepository;
  /** @var  PreprocessingsRepository $preprocessingsRepository */
  private $preprocessingsRepository;

  const NEW_PREPROCESSING_EACHONE_NAME='Each value - one category';

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
   * Funkce vracející instanci preprocessingu typu each value - one category
   * @param $format
   * @return Preprocessing
   */
  public function findPreprocessingEachOne($format){
    if (!$format instanceof Format){
      $format=$this->findFormat($format);
    }
    try{
      $preprocessings=$format->preprocessings;
      if (!empty($preprocessings)){
        foreach ($preprocessings as $preprocessing){
          if (isset($preprocessing->specialType) && $preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
            return $preprocessing;
          }
        }
      }
    }catch (\Exception $e){/*chybu ignorujeme*/}
    $preprocessing=new Preprocessing();
    $preprocessing->name=self::NEW_PREPROCESSING_EACHONE_NAME;
    $preprocessing->specialType=Preprocessing::SPECIALTYPE_EACHONE;
    $preprocessing->format=$format;
    $this->savePreprocessing($preprocessing);
    return $preprocessing;
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
   * Funkce pro nalezení metaatributu se zadaným jménem
   * @param string $metaAttributeName
   * @return MetaAttribute
   * @throws \Exception
   */
  public function findMetaAttributeByName($metaAttributeName){
    $metaAttributes=$this->metaAttributesRepository->findMetaAttributes(array('name'=>$metaAttributeName));
    if (count($metaAttributes)==1){
      return $metaAttributes[0];
    }

    throw new \Exception('Meta-attribute with specified name was not found!');
  }

  /**
   * Funkce pro nalezení formátu se zadaným názvem v rámci daného metaatributu
   * @param MetaAttribute|string $metaAttribute
   * @param string $formatName
   * @throws \Exception
   * @return Format
   */
  public function findFormatByName($metaAttribute,$formatName){
    if (!($metaAttribute instanceof MetaAttribute)){
      $metaAttribute=$this->findMetaAttribute($metaAttribute);
    }
    $formats=$metaAttribute->formats;
    if (count($formats)>0){
      foreach ($formats as $format){
        if ($format->name==$formatName){
          return $format;
        }
      }
    }

    throw new \Exception('Format with specified name was not found!');
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
  public function saveFormat(Format &$format){
    $this->formatsRepository->saveFormat($format);
  }

  /**
   * @param Preprocessing $preprocessing
   */
  public function savePreprocessing(Preprocessing &$preprocessing){
    $this->preprocessingsRepository->savePreprocessing($preprocessing);
  }



  public function __construct(MetaAttributesRepository $metaAttributesRepository,FormatsRepository $formatsRepository, PreprocessingsRepository $preprocessingsRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
    $this->preprocessingsRepository=$preprocessingsRepository;
  }

} 