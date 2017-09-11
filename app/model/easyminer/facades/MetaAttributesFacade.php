<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use EasyMinerCenter\Model\EasyMiner\Entities\MetaAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Repositories\IntervalsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\MetaAttributesRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\FormatsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\PreprocessingsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\ValuesBinsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\ValuesRepository;

/**
 * Class MetaAttributesFacade - facade for work with metaattributes
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
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
  /** @var UsersFacade $usersFacade */
  private $usersFacade;
  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;

  const FORMAT_CREATION_MAX_VALUES_COUNT=100;


  /**
   * @param MetaAttributesRepository $metaAttributesRepository
   * @param FormatsRepository $formatsRepository
   * @param PreprocessingsRepository $preprocessingsRepository
   * @param ValuesBinsRepository $valuesBinsRepository
   * @param ValuesRepository $valuesRepository
   * @param IntervalsRepository $intervalsRepository
   * @param UsersFacade $usersFacade
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function __construct(MetaAttributesRepository $metaAttributesRepository,
                              FormatsRepository $formatsRepository,
                              PreprocessingsRepository $preprocessingsRepository,
                              ValuesBinsRepository $valuesBinsRepository,
                              ValuesRepository $valuesRepository,
                              IntervalsRepository $intervalsRepository,
                              UsersFacade $usersFacade,
                              DatasourcesFacade $datasourcesFacade){
    $this->metaAttributesRepository=$metaAttributesRepository;
    $this->formatsRepository=$formatsRepository;
    $this->preprocessingsRepository=$preprocessingsRepository;
    $this->valuesBinsRepository=$valuesBinsRepository;
    $this->valuesRepository=$valuesRepository;
    $this->intervalsRepository=$intervalsRepository;
    $this->usersFacade=$usersFacade;
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * Method for basic creating of new Format based on the given data column
   * @param DatasourceColumn $datasourceColumn
   * @param User $user
   * @return Format
   */
  public function simpleCreateMetaAttributeWithFormatFromDatasourceColumn(DatasourceColumn $datasourceColumn, User $user) {
    $metaAttribute=$this->findOrCreateMetaAttributeWithName($datasourceColumn->name);
    $datasource=$datasourceColumn->datasource;
    return $this->createFormatFromDatasourceColumn($metaAttribute, $datasource->name, $datasourceColumn, false, $user);
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
   * @param string $metaAttributeName
   * @return MetaAttribute
   */
  public function findOrCreateMetaAttributeWithName($metaAttributeName){
    try{
      $metaAttribute=$this->findMetaAttributeByName($metaAttributeName);
    }catch (\Exception $e){}
    if (empty($metaAttribute)||(!($metaAttribute instanceof MetaAttribute))){
      $metaAttribute=new MetaAttribute();
      $metaAttribute->name=$metaAttributeName;
      $this->saveMetaAttribute($metaAttribute);
    }
    return $metaAttribute;
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
   * Method for creating new Format based on values from data column
   * @param MetaAttribute $metaAttribute
   * @param string $formatName
   * @param DatasourceColumn $datasourceColumn
   * @param bool $formatShared=false
   * @param User $user
   * @return Format
   */
  public function createFormatFromDatasourceColumn(MetaAttribute $metaAttribute, $formatName, DatasourceColumn $datasourceColumn, $formatShared=false, User $user){
    //prepare new format and save it...
    $format=new Format();
    $format->dataType=($datasourceColumn->isNumericType()?Format::DATATYPE_INTERVAL:Format::DATATYPE_VALUES);
    $format->name=$formatName;
    $format->metaAttribute=$metaAttribute;
    $format->user=$user;
    if ($formatShared){
      $format->shared=true;
    }else{
      $format->shared=false;
    }
    $this->saveFormat($format);
    //update format definition range
    $this->updateFormatFromDatasourceColumn($format,$datasourceColumn);
    return $format;
  }

  /**
   * TODO implementovat...
   * @param Format $format
   * @param DatasourceColumn $datasourceColumn
   */
  public function updateFormatFromDatasourceColumn(Format $format, DatasourceColumn $datasourceColumn){
    #region load dbField data
    /** @var IDatabase $datasourceDatabase */
    $datasourceDatabase=$this->datasourcesFacade->getDatasourceDatabase($datasourceColumn->datasource);
    $dbDatasource=$datasourceDatabase->getDbDatasource($datasourceColumn->datasource->dbDatasourceId);
    $dbField=$datasourceDatabase->getDbField($dbDatasource,$datasourceColumn->dbDatasourceFieldId);
    #endregion load dbField data

    if ($datasourceColumn->isNumericType()){
      #region define range using interval
      //load stats from datasource
      $datasourceColumnValuesStats=$datasourceDatabase->getDbValuesStats($dbField);
      //create interval from loaded stats and update format range
      $newInterval=Interval::create(Interval::CLOSURE_CLOSED,$datasourceColumnValuesStats->min,$datasourceColumnValuesStats->max,Interval::CLOSURE_CLOSED);
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
      #endregion define range using interval
    }else{
      $datasourceColumnValues=$datasourceDatabase->getDbValues($dbField,0,self::FORMAT_CREATION_MAX_VALUES_COUNT);
      //TODO vyřešit situaci, kdy bude datasource column obsahovat příliš mnoho hodnot
      $existingValues=[];
      $values=$format->values;
      if (!empty($values)){
        foreach($values as $value){
          $existingValues[]=$value->value;
        }
      }
      if (!empty($datasourceColumnValues)){
        foreach($datasourceColumnValues as $datasourceColumnValue){
          if (!in_array($datasourceColumnValue->value,$existingValues)){
            $valueObject=new Value();
            $valueObject->format=$format;
            $valueObject->value=$datasourceColumnValue->value;
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
    $params=array_merge($paramsArr,$params);
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
    $this->savePreprocessing($preprocessing);
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
   * @param Preprocessing $preprocessing
   */
  public function savePreprocessing(Preprocessing &$preprocessing){
    $this->preprocessingsRepository->persist($preprocessing);
  }


  /**
   * @param Format|int $format
   * @param string $value
   * @return Value
   * @throws \Exception
   */
  public function findValue($format,$value){
    if (!($format instanceof Format)){
      $format=$this->formatsRepository->find($format);
    }
    return $this->valuesRepository->findBy([
      'format_id'=>$format->formatId,
      'value'=>$value
    ]);
  }

  /**
   * Method for finding a Value from given Format, if it does not exist, creates a new one
   * @param Format|int $format
   * @param string $value
   * @return Value
   */
  public function findOrSaveValue($format, $value){
    if (!($format instanceof Format)){
      $format=$this->formatsRepository->find($format);
    }
    try{
      $valueObject=$this->findValue($format, $value);
    }catch(\Exception $e){
      //save value, if it was not found...
      $valueObject=new Value();
      $valueObject->format=$format;
      $valueObject->value=$value;
      $this->saveValue($valueObject);
    }
    return $valueObject;
  }

  /**
   * Method for preparation new preprocessing using the array with input params
   * @param Format $format
   * @param array $definition
   * @return Preprocessing
   */
  public function generateNewPreprocessingFromDefinitionArray(Format $format, array $definition){
    $preprocessing=new Preprocessing();
    $preprocessing->name=date('c');
    $preprocessing->user=null;
    $preprocessing->format=$format;
    $preprocessingType=Preprocessing::decodeAlternativePrepreprocessingTypeIdentification(@$definition['type']);
    if (!in_array($preprocessingType,Preprocessing::getPreprocessingTypes())){
      throw  new \InvalidArgumentException('Invalid preprocessing type: '.@$definition['type']);
    }

    if ($preprocessingType==Preprocessing::TYPE_EACHONE){
      #region eachOne
      $preprocessing->name=Preprocessing::NEW_PREPROCESSING_EACHONE_NAME;
      return $this->findPreprocessingEachOne($format);
      #endregion eachOne
    }elseif($preprocessingType==Preprocessing::TYPE_EQUIFREQUENT_INTERVALS){
      #region equifrequent intervals
      $specialParams=['count'=>floatval($definition['count'])];
      if (isset($definition['from']) && isset($definition['to'])){
        $specialParams['from']=floatval($definition['from']);
        $specialParams['to']=floatval($definition['to']);
        if ($specialParams['from']==$specialParams['to']){
          throw new \InvalidArgumentException('From and To params of equifrequent intervals have to be different.');
        }
        if ($specialParams['from']>$specialParams['to']){
          //if the values have bad order, resort them
          $from=$specialParams['to'];
          $specialParams['to']=$specialParams['from'];
          $specialParams['from']=$from;
        }
        $preprocessing->name=$specialParams['count'].' equfreq. int. from '.$specialParams['from'].' to '.$specialParams['to'];
      }else{
        $preprocessing->name=$specialParams['count'].' equfreq. int.';
      }
      if ($specialParams['count']<=1){
        throw new \InvalidArgumentException('Count param of equifrequent intervals has to be greater than 1.');
      }
      $preprocessing->specialType=$preprocessingType;
      $preprocessing->setSpecialTypeParams($specialParams);
      #endregion equifrequent intervals
    }elseif($preprocessingType==Preprocessing::TYPE_EQUISIZED_INTERVALS){
      #region equisized intervals
      $specialParams=['support'=>floatval(@$definition['support'])];
      if ($specialParams['support']<=0){
        throw new \InvalidArgumentException('Min support of equisized intervals have to be greater than 0.');
      }
      if (isset($definition['from']) && isset($definition['to'])){
        $specialParams['from']=floatval($definition['from']);
        $specialParams['to']=floatval($definition['to']);
        if ($specialParams['from']==$specialParams['to']){
          throw new \InvalidArgumentException('From and To params of equisized intervals have to be different.');
        }
        if ($specialParams['from']>$specialParams['to']){
          //if the values have bad order, resort them
          $from=$specialParams['to'];
          $specialParams['to']=$specialParams['from'];
          $specialParams['from']=$from;
        }
        $preprocessing->name='Equisized int. from '.$specialParams['from'].' to '.$specialParams['to'];
      }else{
        $preprocessing->name='Equisized int.';
      }
      $preprocessing->specialType=$preprocessingType;
      $preprocessing->setSpecialTypeParams($specialParams);
      #endregion equisized intervals
    }elseif($preprocessingType==Preprocessing::TYPE_EQUIDISTANT_INTERVALS){
      #region equidistant intervals
      $specialParams=['from'=>floatval($definition['from']),'to'=>floatval($definition['to'])];
      if ($specialParams['from']==$specialParams['to']){
        throw new \InvalidArgumentException('From and To params of equifrequent intervals have to be different.');
      }
      if ($specialParams['from']>$specialParams['to']){
        //if the values have bad order, resort them
        $from=$specialParams['to'];
        $specialParams['to']=$specialParams['from'];
        $specialParams['from']=$from;
      }

      if (!empty($definition['length'])&&(floatval($definition['length'])>0)){
        $intervalLength=$definition['length'];
      }else{
        //setting using count of intervals => transform it to length of interval
        $specialParams['count']=floatval($definition['count']);
        if ($specialParams['count']<=1){
          throw new \InvalidArgumentException('Count param of equifrequent intervals has to be greater than 1.');
        }
        $intervalLength=($specialParams['to']-$specialParams['from'])/$specialParams['count'];
        //rounding to "nicer" numbers
        if ($intervalLength>1){
          $intervalLength=round($intervalLength*1000)/1000;
        }else{
          $intervalLength=round($intervalLength*1000000)/1000000;
        }
      }
      if (empty($intervalLength) || $intervalLength==0){
        throw new \InvalidArgumentException('Length of equifrequent intervals has to be greater than 0.');
      }

      if (!empty($definition['name'])){
        $preprocessing->name=$definition['name'];
      }
      $this->savePreprocessing($preprocessing);

      //generate intervals
      $start=$specialParams['from'];
      do{
        $interval=new Interval();
        $interval->leftMargin=$start;
        $interval->leftClosure=Interval::CLOSURE_CLOSED;
        $interval->rightMargin=min($start+$intervalLength,$specialParams['to']);
        if ($start+$intervalLength>=$specialParams['to']){
          $interval->rightClosure=Interval::CLOSURE_CLOSED;
        }else{
          $interval->rightClosure=Interval::CLOSURE_OPEN;
        }
        $interval->format=$format;
        $this->saveInterval($interval);
        $valuesBin=new ValuesBin();
        $valuesBin->format=$format;
        $valuesBin->name=$interval->__toString();
        $this->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $valuesBin->addToIntervals($interval);
        $this->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $preprocessing->addToValuesBins($valuesBin);

        $start+=$intervalLength;
      }while($start<$specialParams['to']);
      #endregion equidistant intervals
    }elseif($preprocessingType==Preprocessing::TYPE_INTERVAL_ENUMERATION){
      #region interval enumeration
      $bins=$definition['bins'];
      if (empty($bins) || !is_array($bins)){
        throw new \InvalidArgumentException('You have to define one or more nominal bins!');
      }

      if (!empty($definition['name'])){
        $preprocessing->name=$definition['name'];
      }
      $this->savePreprocessing($preprocessing);

      $exisingBinNames=[];
      foreach($bins as $bin){
        //save all bins to DB
        #region solve the uniqueness of BIN names
        $binName=trim(@$bin['name']);
        if (empty($bin['intervals'])){
          continue;
        }
        if ($binName==''){
          $binName='noname';
        }
        $x=1;
        $newBinName=$binName;
        while (in_array($newBinName,$exisingBinNames)){
          $x++;
          $newBinName=$binName.$x;
        }
        $binName=$newBinName;
        #endregion solve the uniqueness of BIN names

        $valuesBin=new ValuesBin();
        $valuesBin->name=$binName;
        $valuesBin->format=$format;
        $this->saveValuesBin($valuesBin);

        /** @var Interval[] $intervals */
        $intervals=[];
        foreach($bin['intervals'] as $intervalConfig){
          $interval=new Interval();
          $interval->format=$format;
          $interval->leftMargin=$intervalConfig['leftMargin'];
          $interval->rightMargin=$intervalConfig['rightMargin'];
          $interval->setClosure($intervalConfig['closure']);
          if (!empty($intervals)){
            //check, if the interval is not in overlap with another, already defined interval
            $intervalOverlap=false;
            foreach($intervals as $existingInterval){
              if ($interval->isInOverlapWithInterval($existingInterval)){
                $intervalOverlap=true;
                break;
              }
            }
            if ($intervalOverlap){continue;}
          }
          $this->saveInterval($interval);
          /** @noinspection PhpMethodParametersCountMismatchInspection */
          $valuesBin->addToIntervals($interval);
          $intervals[]=$interval;
        }
        $this->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $preprocessing->addToValuesBins($valuesBin);
      }
      #endregion interval enumeration
    }elseif($preprocessingType==Preprocessing::TYPE_NOMINAL_ENUMERATION){
      #region nominal enumeration
      $bins=$definition['bins'];
      if (empty($bins) || !is_array($bins)){
        throw new \InvalidArgumentException('You have to define one or more nominal bins!');
      }

      if (!empty($definition['name'])){
        $preprocessing->name=$definition['name'];
      }
      $this->savePreprocessing($preprocessing);

      $exisingBinNames=[];
      foreach($bins as $bin){
        //save all BINs to DB
        #region solve the uniqueness of BIN names
        $binName=trim(@$bin['name']);
        if (empty($bin['values'])){
          continue;
        }
        if ($binName==''){
          $binName='noname';
        }
        $x=1;
        $newBinName=$binName;
        while (in_array($newBinName,$exisingBinNames)){
          $x++;
          $newBinName=$binName.$x;
        }
        $binName=$newBinName;
        #endregion solve the uniqueness of BIN names

        $valuesBin=new ValuesBin();
        $valuesBin->name=$binName;
        $valuesBin->format=$format;
        $this->saveValuesBin($valuesBin);

        foreach($bin['values'] as $value){
          $valueItem=$this->findOrSaveValue($format,$value);
          /** @noinspection PhpMethodParametersCountMismatchInspection */
          $valuesBin->addToValues($valueItem);
        }
        $this->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $preprocessing->addToValuesBins($valuesBin);
      }
      #endregion nominal enumeration
    }else{
      throw  new \InvalidArgumentException('Invalid preprocessing type: '.@$definition['type']);
    }

    $this->savePreprocessing($preprocessing);
    return $preprocessing;
  }





}