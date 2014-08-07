<?php
namespace App\Model\Rdf\Repositories;

use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;

class KnowledgeRepository extends BaseRepository{

  /**
   * @param null|array $params
   * @param int $limit
   * @param int $offset
   * @return MetaAttribute[]
   */
  public function findMetaattributes($params=null,$limit=-1,$offset=-1){//TODO params,limit
    $result=$this->executeQuery(MetaAttribute::getLoadQuery());
    if ($result && !empty($result['rows'])){
      $output=array();
      foreach ($result['rows'] as $row){
        $metaattribute=new MetaAttribute();
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
  public function saveMetaattribute(MetaAttribute $metaAttribute){
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
    //TODO
  }

  /**
   * @param null|array $params
   * @param int        $limit
   * @param int        $offset
   * @return Rule[]
   */
  public function findRules($params=null,$limit=-1,$offset=-1){
    //TODO
  }

  /**
   * @param Format $format
   * return bool
   */
  public function saveFormat(Format $format){
    //TODO
  }

} 