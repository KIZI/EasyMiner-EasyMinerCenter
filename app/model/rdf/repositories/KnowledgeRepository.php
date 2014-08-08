<?php
namespace App\Model\Rdf\Repositories;

use App\Model\Rdf\Entities\BaseEntity;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Class KnowledgeRepository
 * @package App\Model\Rdf\Repositories
 */
class KnowledgeRepository extends BaseRepository{

  /**
   * @param null|array $params
   * @param int $limit
   * @param int $offset
   * @return MetaAttribute[]
   */
  public function findMetaattributes($params=null,$limit=-1,$offset=-1){
    #region params
    $filterSparql='';
    if (!empty($params)){
      if (!empty($params['sparql'])){
        $filterSparql.=' && '.$params['sparql'];
      }
      if ($filterSparql!=''){
        $filterSparql.=Strings::substring($filterSparql,4);
      }
    }

    #endregion params
    $result=$this->executeQuery(MetaAttribute::getLoadQuery('',$filterSparql),$limit,$offset);
    if ($result && !empty($result['rows'])){
      $output=array();
      foreach ($result['rows'] as $row){
        $metaattribute=new MetaAttribute($this);
        $metaattribute->setKnowledgeRepository($this);
        $metaattribute->prepareEntity($row);
        $output[]=$metaattribute;
      }
      return $output;
    }
    return null;
  }

  /**
   * @param MetaAttribute $metaAttribute
   * @return bool
   */
  public function saveMetaattribute(MetaAttribute &$metaAttribute){
    //TODO kontrola podřízených entit
    $this->saveEntity($metaAttribute);
  }

  /**
   * @param string $uri
   * @return MetaAttribute
   */
  public function findMetaattribute($uri){
    return $this->findEntity($uri,'MetaAttribute');
  }

  /**
   * @param string $uri
   * @return Format
   */
  public function findFormat($uri){
    return $this->findEntity($uri,'Format');
  }

  /**
   * @param string $uri
   * @return Rule
   */
  public function findRule($uri){
    return $this->findEntity($uri,'Rule');
  }

  /**
   * @param null|array $params
   * @param int        $limit
   * @param int        $offset
   * @return Format[]
   */
  public function findFormats($params=null,$limit=-1,$offset=-1){
    #region params
    $filterSparql='';
    if (!empty($params)){
      //TODO params

      if ($filterSparql!=''){
        $filterSparql.=Strings::substring($filterSparql,4);
      }
    }

    #endregion params
    $result=$this->executeQuery(Format::getLoadQuery('',$filterSparql),$limit,$offset);
    if ($result && !empty($result['rows'])){
      $output=array();
      foreach ($result['rows'] as $row){
        $format=new Format();
        $format->setKnowledgeRepository($this);
        $format->prepareEntity($row);
        $output[]=$format;
      }
      return $output;
    }
    return null;
  }

  /**
   * @param null|array $params
   * @param int        $limit
   * @param int        $offset
   * @return Rule[]
   */
  public function findRules($params=null,$limit=-1,$offset=-1){
    #region params
    $filterSparql='';
    if (!empty($params)){
      //TODO params

      if ($filterSparql!=''){
        $filterSparql.=Strings::substring($filterSparql,4);
      }
    }

    #endregion params
    $result=$this->executeQuery(MetaAttribute::getLoadQuery('',$filterSparql),$limit,$offset);
    if ($result && !empty($result['rows'])){
      $output=array();
      foreach ($result['rows'] as $row){
        $format=new Format();
        $format->setKnowledgeRepository($this);
        $format->prepareEntity($row);
        $output[]=$format;
      }
      return $output;
    }
    return null;
  }

  /**
   * @param Format $format
   * return bool
   */
  public function saveFormat(Format $format){
    //TODOStr
  }

  /**
   * Funkce pro načtení entit...
   * @param string $functionName
   * @param array $params
   * @return BaseEntity[]|BaseEntity|mixed
   * @throws \Nette\Application\BadRequestException
   */
  public function __call($functionName,$params){
    $callFunctionName='';
    if (Strings::startsWith($functionName,'find')){
      if (Strings::endsWith($functionName,'s')){
        //chceme načítat kolekci entit
        $entityClassName=Strings::substring($functionName,4,Strings::length($functionName)-5);
        $callFunctionName='findEntities';
      }else{
        //chceme načítat jen jednu entitu
        $entityClassName=Strings::substring($functionName,4);
        $callFunctionName='findEntity';
      }
    }
    if (empty($entityClassName) || !class_exists($entityClassName) || !isset($params[0])){
      throw new BadRequestException('Function not exists: '.$functionName);
    }else{
      return $this->$callFunctionName($entityClassName,$params[0]);
    }
  }

} 