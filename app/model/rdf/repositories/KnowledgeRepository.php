<?php
namespace App\Model\Rdf\Repositories;

use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\BaseEntity;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\KnowledgeBase;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
use App\Model\Rdf\Entities\RuleSet;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Class KnowledgeRepository
 * @package App\Model\Rdf\Repositories
 * @method saveRule(Rule $rule,&$urisArr=array())
 * @method saveKnowledgeBase(KnowledgeBase $knowledgeBase,&$urisArr=array())
 */
class KnowledgeRepository extends BaseRepository{

  /**
   * Akce pro smazání pravidla
   * @param Rule $rule
   * @param RuleSet $ruleSet
   * @return bool
   */
  public function deleteRule(Rule $rule,RuleSet $ruleSet=null){
    //TODO pravidlo by mělo být úplně smazáno jen v případě, kdy není v žádném RuleSetu, jinak ho odstraníme jen z daného RuleSetu
    //TODO dodělat
    if ($ruleSet){
      $query=Rule::prepareQueryPrefixes(array('kb'=>"http://easyminer.eu/kb/")).' DELETE FROM <'.BaseEntity::BASE_ONTOLOGY.'> {'.BaseEntity::quoteUri($ruleSet->uri).' ?relation '.BaseEntity::quoteUri($rule->uri).'}';
      $this->executeQuery($query);
    }else{
      $query1=Rule::prepareQueryPrefixes(array('kb'=>"http://easyminer.eu/kb/")).' DELETE FROM <'.BaseEntity::BASE_ONTOLOGY.'> {?entity ?relation '.BaseEntity::quoteUri($rule->uri).'}';
      $query2=Rule::prepareQueryPrefixes(array('kb'=>"http://easyminer.eu/kb/")).' DELETE FROM <'.BaseEntity::BASE_ONTOLOGY.'> {'.BaseEntity::quoteUri($rule->uri).' ?relation ?entity}';
      $this->executeQueries(array($query1,$query2));
    }
    return true;
  }

  /**
   * @param null|array $params
   * @param int $limit = -1
   * @param int $offset = -1
   * @return RuleSet[]|null
   */
  public function findRuleSets($params=null,$limit=-1,$offset=-1){
    return $this->findEntities('RuleSet',$params,null,$limit,$offset);
  }

  /**
   * @param null|array $params
   * @param int $limit = -1
   * @param int $offset = -1
   * @return Attribute[]|null
   */
  public function findAttributes($params=null,$limit=-1,$offset=-1){
    return $this->findEntities('Attribute',$params,null,$limit,$offset);
  }

  /**
   * @param null|array $params
   * @param int $limit = -1
   * @param int $offset = -1
   * @return KnowledgeBase[]|null
   */
  public function findKnowledgeBases($params=null,$limit=-1,$offset=-1){
    return $this->findEntities('KnowledgeBase',$params,null,$limit,$offset);
  }

  /**
   * @param null|array $params
   * @param int $limit=-1
   * @param int $offset=-1
   * @return MetaAttribute[]
   *
  public function findMetaAttributes($params=null,$limit=-1,$offset=-1){
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
    $result=$this->executeQuery(MetaAttribute::getLoadQuery('',$filterSparql),'raw',$limit,$offset);
    if ($result && !empty($result['rows'])){
      $output=array();
      foreach ($result['rows'] as $row){
        $metaAttribute=new MetaAttribute($this);
        $metaAttribute->setKnowledgeRepository($this);
        $metaAttribute->prepareEntity($row);
        $output[]=$metaAttribute;
      }
      return $output;
    }
    return null;
  }*/

  /**
   * @param MetaAttribute $metaAttribute
   */
  public function saveMetaattribute(MetaAttribute &$metaAttribute,&$urisArr=array()){
    $this->saveEntity($metaAttribute,$urisArr);
  }

  /**
   * @param RuleSet $ruleSet
   */
  public function saveRuleSet(RuleSet &$ruleSet,&$urisArr=array()){
    $this->saveEntity($ruleSet,$urisArr);
  }

  /**
   * @param Attribute $attribute
   */
  public function saveAttribute(Attribute &$attribute,&$urisArr=array()){
    $this->saveEntity($attribute,$urisArr);
  }

  /**
   * @param string $uri
   * @return MetaAttribute
   */
  public function findMetaAttribute($uri){
    return $this->findEntity('MetaAttribute',$uri);
  }

  /**
   * @param string $uri
   * @return Format
   */
  public function findFormat($uri){
    return $this->findEntity('Format',$uri);
  }

  /**
   * @param string $uri
   * @return Attribute
   */
  public function findAttribute($uri){
    return $this->findEntity('Attribute',$uri);
  }

  /**
   * @param string $uri
   * @return Rule
   */
  public function findRule($uri){
    return $this->findEntity('Rule',$uri);
  }

  public function fxindValuesBin($uri){
    return $this->findEntity('ValuesBin',$uri);
  }

  /**
   * @param string $uri
   * @return RuleSet
   */
  public function findRuleSet($uri){
    return $this->findEntity('RuleSet',$uri);
  }

  /**
   * @param string $uri
   * @return KnowledgeBase
   */
  public function findKnowledgeBase($uri){
    return $this->findEntity('KnowledgeBase',$uri);
  }

  /**
   * @param null|array $params
   * @param int        $limit
   * @param int        $offset
   * @return Format[]
   *
  public function findFormats($params=null,$limit=-1,$offset=-1){
    #region params
    $filterSparql='';

    if (!empty($params)){
      //TODO params
      if (!empty($params['sparql'])){
        $filterSparql=' .  '.$params['sparql'];
      }

      if ($filterSparql!=''){
        $filterSparql=Strings::substring($filterSparql,4);
      }
    }
    #endregion params
    $result=$this->executeQuery(Format::getLoadQuery('',$filterSparql),$limit,$offset);;
    if ($result && !empty($result['result']['rows'])){
      $output=array();
      foreach ($result['result']['rows'] as $row){
        $format=new Format();
        $format->setKnowledgeRepository($this);
        $format->prepareEntity($row);
        $output[]=$format;
      }
      return $output;
    }
    return null;
  }*/

  /**
   * @param null|array $params
   * @param int        $limit
   * @param int        $offset
   * @return Rule[]
   *
  public function findRulesx($params=null,$limit=-1,$offset=-1){
    #region params
    $filterSparql='';
    if (!empty($params)){
      //TODO params

      if ($filterSparql!=''){
        $filterSparql.=Strings::substring($filterSparql,4);
      }
    }

    #endregion params
    $result=$this->executeQuery(Rule::getLoadQuery('',$filterSparql),$limit,$offset);
    if ($result && !empty($result['result']['rows'])){
      $output=array();
      foreach ($result['result']['rows'] as $row){
        $rule=new Rule();
        $rule->setKnowledgeRepository($this);
        $rule->prepareEntity($row);
        $output[]=$rule;
      }
      return $output;
    }
    return null;
  }*/

  /**
   * @param Format $format
   * return bool
   */
  public function saveFormat(Format &$format,&$urisArr=array()){
    $this->saveEntity($format,$urisArr);
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
    if (Strings::startsWith($functionName,'find') || Strings::startsWith($functionName,'save')){
      if (Strings::endsWith($functionName,'s')){
        //chceme načítat kolekci entit
        $entityClassName=Strings::substring($functionName,4,Strings::length($functionName)-5);
        $callFunctionName='findEntities';
      }else{
        //chceme načítat jen jednu entitu
        $entityClassName=Strings::substring($functionName,4);
        $callFunctionName=Strings::substring($functionName,0,4).'Entity';
      }
    }
    if (empty($entityClassName) || !(class_exists($entityClassName) || class_exists(self::BASIC_ENTITY_NAMESPACE.'\\'.$entityClassName)) || !isset($params[0])){
      throw new BadRequestException('Function not exists: '.$functionName);
    }else{
      if (Strings::startsWith($functionName,'save')){
        if (isset($params[1])){
          return $this->$callFunctionName($params[0],$params[1]);
        }else{
          return $this->$callFunctionName($params[0]);
        }

      }else{
        return $this->$callFunctionName($entityClassName,$params[0]);
      }
    }
  }

} 