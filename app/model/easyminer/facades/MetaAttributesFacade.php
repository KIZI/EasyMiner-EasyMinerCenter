<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\Data\Entities\DbColumnValuesStatistic;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Format;
use App\Model\EasyMiner\Entities\Interval;
use App\Model\EasyMiner\Entities\MetaAttribute;
use App\Model\EasyMiner\Entities\Preprocessing;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Entities\Value;
use App\Model\EasyMiner\Entities\ValuesBin;
use App\Model\EasyMiner\Repositories\IntervalsRepository;
use App\Model\EasyMiner\Repositories\MetaAttributesRepository;
use App\Model\EasyMiner\Repositories\FormatsRepository;
use App\Model\EasyMiner\Repositories\PreprocessingsRepository;
use App\Model\EasyMiner\Repositories\ValuesBinsRepository;
use App\Model\EasyMiner\Repositories\ValuesRepository;

class MetaAttributesFacade {
  /** @var MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var FormatsRepository $formatsRepository */
  private $formatsRepository;
  /** @var PreprocessingsRepository $preprocessingsRepository */
  private $preprocessingsRepository;
  /** @var ValuesBinsRepository $valuesBinsRepository */
  private $valuesBinsRepository;
  /** @var ValuesRepository $valuesRepository */
  private $valuesRepository;
  /** @var IntervalsRepository $intervalsRepository */
  private $intervalsRepository;

  /**
   * @param MetaAttributesRepository $metaAttributesRepository
   * @param FormatsRepository $formatsRepository
   * @param PreprocessingsRepository $preprocessingsRepository
   * @param ValuesBinsRepository $valuesBinsRepository
   * @param ValuesRepository $valuesRepository
   * @param IntervalsRepository $intervalsRepository
   */
  public function __construct(MetaAttributesRepository $metaAttributesRepository,
                              FormatsRepository $formatsRepository,
                              PreprocessingsRepository $preprocessingsRepository,
                              ValuesBinsRepository $valuesBinsRepository,
                              ValuesRepository $valuesRepository,
                              IntervalsRepository $intervalsRepository){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
    $this->preprocessingsRepository=$preprocessingsRepository;
    $this->valuesBinsRepository=$valuesBinsRepository;
    $this->valuesRepository=$valuesRepository;
    $this->intervalsRepository=$intervalsRepository;
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
   * Funkce pro aktualizaci formátu na základě hodnot z daného DatasourceColumn
   * @param Format $format
   * @param DatasourceColumn $datasourceColumn
   * @param DbColumnValuesStatistic $columnValuesStatistic
   */
  public function updateFormatFromDatasourceColumn(Format $format, DatasourceColumn $datasourceColumn, DbColumnValuesStatistic $columnValuesStatistic){
    if ($format->dataType==Format::DATATYPE_INTERVAL){
      $newInterval=Interval::create(Interval::CLOSURE_CLOSED,$columnValuesStatistic->minValue,$columnValuesStatistic->maxValue,Interval::CLOSURE_CLOSED);
      $newInterval->format=$format;
      $intervals=$format->intervals;
      if (!empty($intervals)){
        if (count($intervals)==1){
          $originalInterval=$intervals[0];
          if ($originalInterval->leftMargin>$newInterval->leftMargin || ($originalInterval->leftMargin==$newInterval->leftMargin && $originalInterval->leftClosure=Interval::CLOSURE_OPEN)){
            $originalInterval->leftMargin=$newInterval->leftMargin;
            $originalInterval->leftClosure=$newInterval->leftClosure;
          }
          if ($originalInterval->rightMargin<$newInterval->rightMargin || ($originalInterval->rightMargin==$newInterval->rightMargin && $originalInterval->rightClosure=Interval::CLOSURE_OPEN)){
            $originalInterval->rightMargin=$newInterval->rightMargin;
            $originalInterval->rightClosure=$newInterval->rightClosure;
          }
          $this->saveInterval($originalInterval);
        }else{
          //FIXME možné rozšíření množiny intervalů
        }
      }else{
        $this->saveInterval($newInterval);
      }
    }else{
      $existingValues=[];
      $values=$format->values;
      if (!empty($values)){
        foreach($values as $value){
          $existingValues[]=$value->value;
        }
      }
      if (!empty($columnValuesStatistic->valuesArr)){
        foreach($columnValuesStatistic->valuesArr as $value=>$count){
          if (!in_array($value,$existingValues)){
            $valueObject=new Value();
            $valueObject->format=$format;
            $valueObject->value=$value;
            $this->saveValue($valueObject);
          }
        }
      }
    }
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
    return $this->preprocessingsRepository->findAllBy($paramsArr,$offset,$limit);
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

  /**
   * @param int $id
   * @return Preprocessing
   */
  public function findPreprocessing($id) {
    return $this->preprocessingsRepository->find($id);
  }

  /**
   * @param Format|int $format
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
    $preprocessing->name=Preprocessing::NEW_PREPROCESSING_EACHONE_NAME;
    $preprocessing->specialType=Preprocessing::SPECIALTYPE_EACHONE;
    $preprocessing->shared=true;
    $preprocessing->format=$format;
    $this->preprocessingsRepository->persist($preprocessing);
    return $preprocessing;
  }

  /**
   * @param ValuesBin $valuesBin
   */
  public function saveValuesBin(ValuesBin &$valuesBin){
    $this->valuesBinsRepository->persist($valuesBin);
  }
  /**
   * @param Interval $interval
   */
  public function saveInterval(Interval &$interval){
    $this->intervalsRepository->persist($interval);
  }
  /**
   * @param Value $value
   */
  public function saveValue(Value &$value){
    $this->valuesRepository->persist($value);
  }

  /**
   * @param Format|int $format
   * @param string $value
   * @return Value
   * @throws \Exception
   */
  public function findValue($format,$value){//TODO má to být tady?
    if (!($format instanceof Format)){
      $format=$this->formatsRepository->find($format);
    }
    return $this->valuesRepository->findBy([
      'format_id'=>$format->formatId,
      'value'=>$value
    ]);
  }

}