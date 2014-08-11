<?php

namespace App\Model\Rdf\Entities;

use App\Model\Rdf\Repositories\KnowledgeRepository;
use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class BaseEntity
 * @package App\Model\Rdf\Entities
 */
abstract class BaseEntity extends Object{
  const BASE_ONTOLOGY='http://easyminer.eu/kb';
  /** @var string $uri */
  private $uri='';
  protected static $mappedProperties=array();
  /** @var KnowledgeRepository $repository */
  protected $repository;

  public function __construct(KnowledgeRepository $knowledgeRepository=null){
    $this->repository=$knowledgeRepository;
  }

  public function setKnowledgeRepository(KnowledgeRepository $repository){
    $this->repository=$repository;
  }

  public function getUri(){
    return $this->uri;
  }
  public function setUri($uri){
    $this->uri=$uri;
  }

  public static function getMappedProperties(){
    if (!empty(static::$mappedProperties[get_called_class()])){
      return static::$mappedProperties[get_called_class()];
    }
    $classReflection=new \Nette\Reflection\ClassType(get_called_class());
    //$classReflection=$this->getReflection();
    $annotations=$classReflection->getAnnotations();
    $output=array('entities'=>array(),'literals'=>array());
    if (!empty($annotations['rdfEntity'])){
      foreach($annotations['rdfEntity'] as $annotation){
        $property=$annotation['property'];
        if ($property[0]=='$'){
          $property=Strings::substring($property,1);
          $annotation['property']=$property;
        }
        if (empty($property)){continue;}
        $output['entities'][$property]=$annotation;
      }
    }

    if (!empty($annotations['rdfLiteral'])){
      foreach($annotations['rdfLiteral'] as $annotation){
        $property=$annotation['property'];
        if ($property[0]=='$'){
          $property=$annotation['property'];
          if ($property[0]=='$'){
            $property=Strings::substring($property,1);
            $annotation['property']=$property;
          }
          if (empty($property)){continue;}
          $output['literals'][$property]=$annotation;
        }
      }
    }

    if (!empty($annotations['rdfEntitiesGroup'])){
      foreach($annotations['rdfEntitiesGroup'] as $annotation){
        $property=$annotation['property'];
        if ($property[0]=='$'){
          $property=$annotation['property'];
          if ($property[0]=='$'){
            $property=Strings::substring($property,1);
            $annotation['property']=$property;
          }
          if (empty($property)){continue;}
          $output['entitiesGroups'][$property]=$annotation;
        }
      }
    }
    if (!empty($annotations['rdfClass']) && !empty($annotations['rdfClass'][0])){
        $output['class']=@$annotations['rdfClass'][0]['class'];
    }
    if (!empty($annotations['rdfNamespaces']) && !empty($annotations['rdfNamespaces'][0])){
      $output['namespaces']=@$annotations['rdfNamespaces'][0];
    }
    self::$mappedProperties[get_called_class()]=$output;
    return $output;
  }

  public function &__get($name){
    try{
      return parent::__get($name);
    }catch (\Exception $e){
      $mappedProperties=$this->getMappedProperties();
      if (isset($mappedProperties['entities'][$name]) || isset($mappedProperties['entitiesGroups'][$name]) /*|| isset($mappedProperties['literals'][$name])*/){
/*        if (isset($mappedProperties['literals'][$name])){
          //literály jsou aktuálně načítané rovnou -> nemá smysl je donačítat
          exit('ERROR');
        }*/
        if (isset($mappedProperties['entities'][$name])){
          //načtení jedné související entity
          if (isset($mappedProperties['entities'][$name]['entity'])){
            $function='find'.$mappedProperties['entities'][$name]['entity'].'s';
          }else{
            $function='find'.Strings::firstUpper($name).'s';
          }

          if (isset($mappedProperties['entities'][$name]['relation'])){
            $sparqlQuery='<'.$this->uri.'> '.$mappedProperties['entities'][$name]['relation'].' ?uri';
          }elseif(isset($mappedProperties['entitiesGroups'][$name]['reverseRelation'])){
            $sparqlQuery=' ?uri '.$mappedProperties['entities'][$name]['reverseRelation'].'<'.$this->uri.'> ';
          }

          if (!empty($sparqlQuery)){
            $result = $this->repository->$function(array('sparql'=>$sparqlQuery));
            if ($result && isset($result[0])){
              return $result[0];
            }
          }
        }
        if (isset($mappedProperties['entitiesGroups'][$name])){
          //načtení připojené sady entit
          if (isset($mappedProperties['entitiesGroups'][$name]['entity'])){
            $function='find'.$mappedProperties['entitiesGroups'][$name]['entity'].'s';
          }else{
            $function='find'.Strings::firstUpper($name);
          }

          if (isset($mappedProperties['entitiesGroups'][$name]['relation'])){
            $sparqlQuery='<'.$this->uri.'> '.$mappedProperties['entitiesGroups'][$name]['relation'].' ?uri';
          }elseif(isset($mappedProperties['entitiesGroups'][$name]['reverseRelation'])){
            $sparqlQuery=' ?uri '.$mappedProperties['entitiesGroups'][$name]['reverseRelation'].'<'.$this->uri.'> ';
          }

          if (!empty($sparqlQuery)){
            $result= $this->repository->$function(array('sparql'=>$sparqlQuery));
            return $result;
          }
        }

      }
      //pokud se nepodařilo property donačíst, vyhodíme výjimku...
      throw $e;
    }
  }

  /**
   * @return string[]
   */
  public function getSaveQuery($repository=null){//TODO kontrola, jestli jsou zadané povinné literály!!!
    if (!$repository){$repository=$this->repository;}
    $mappedProperties=$this->getMappedProperties();
    $queryPrefixes=self::prepareQueryPrefixes(@$mappedProperties['namespaces']);
    $queries=array();

    #region vyřešení literálů
    $insertQuery='. <'.$this->uri.'> a '.$mappedProperties['class'];
    if (!empty($mappedProperties['literals']) && is_array($mappedProperties['literals'])){
      foreach ($mappedProperties['literals'] as $literal){
        $property=$literal['property'];
        if (isset($this->$property)){
          //máme updatovat => pokusíme se smazat jednotlivé literály
          $queries[]=$queryPrefixes.'DELETE FROM <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$literal['relation'].' ?value}';
          $insertQuery.='. <'.$this->uri.'> '.$literal['relation'].' '.self::quoteSparql($this->$property);
        }
      }
    }
    if ($insertQuery!=''){
      $insertQuery='INSERT INTO <'.self::BASE_ONTOLOGY.'> {'.Strings::substring($insertQuery,2).'}';
      $queries[]=$queryPrefixes.$insertQuery;
    }
    #endregion vyřešení literálů
    #region vyřešení navázaných entit
    if (!empty($mappedProperties['entities']) && is_array($mappedProperties['entities'])){
      foreach ($mappedProperties['entities'] as $entity){
        $property=$entity['property'];
        if (isset($this->$property) /*&& !empty($this->$property->uri)*/){
          //máme připojenou už namapovanou entitu
          if (!empty($entity['relation'])){
            //nejprve uložíme navázanou entitu
            if (!empty($entity['entity'])){
              $entityName=$entity['entity'];
            }else{
              $entityName=Strings::firstUpper($property);
            }
            $repositorySaveMethod='save'.$entityName;
            $repository->$repositorySaveMethod($this->$property);
            //uložíme relaci připojené entity
            $queries[]=$queryPrefixes.'DELETE FROM <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entity['relation'].' ?relatedUri}';// WHERE {FILTER NOT EXISTS {<'.$this->uri.'> '.$entity['relation'].' <'.$this->$property->uri.'>}}';
            $queries[]=$queryPrefixes.'INSERT INTO <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entity['relation'].' <'.$this->$property->uri.'>}';// WHERE {FILTER NOT EXISTS {<'.$this->uri.'> '.$entity['relation'].' <'.$this->$property->uri.'>}}';
          }/*else{//pravděpodobně není potřeba - ukládáme vždy z té entity, která má zadanou URI
            $queries[]=$queryPrefixes.'DELETE FROM <'.self::BASE_ONTOLOGY.'> {?relatedUri '.$entity['reverseRelation'].' <'.$this->uri.'>} WHERE {FILTER NOT EXISTS {<'.$this->$property->uri.'> '.$entity['reverseRelation'].' <'.$this->uri.'>}}';
            $queries[]=$queryPrefixes.'INSERT INTO <'.self::BASE_ONTOLOGY.'> {<'.$this->$property->uri.'> '.$entity['reverseRelation'].' <'.$this->uri.'>} WHERE {FILTER NOT EXISTS {<'.$this->$property->uri.'> '.$entity['reverseRelation'].' <'.$this->uri.'>}}';
          }*/
        }
      }
    }
    #endregion vyřešení navázaných entit
    #region vyřešení navázaných entitiesGroups
    if (!empty($mappedProperties['entitiesGroups']) && is_array($mappedProperties['entitiesGroups'])){
      foreach ($mappedProperties['entitiesGroups'] as $entitiesGroup){
        $property=$entitiesGroup['property'];
        if (isset($this->$property) && !empty($entitiesGroup['relation'])){
          //zjistíme jméno propojené entity
          if (@$entitiesGroup['entity']!=''){
            $entityName=$entitiesGroup['entity'];
          }else{
            $entityName=$property;
            if (Strings::endsWith($entityName,'s')){
              $entityName=Strings::substring($entityName,0,Strings::length($entityName)-1);
            }
          }
          $entitySaveFunction='save'.Strings::firstUpper($entityName);
          //máme nějaké položky
          if (count($this->$property)){
            //zkontrolujeme, jestli máme již nějaké vazby
            $relatedResult=$repository->executeQuery($queryPrefixes.'SELECT ?relatedUri FROM <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entitiesGroup['relation'].' ?relatedUri}');
            $relatedUrisArr=array();
            if ($relatedResult && !empty($relatedResult['result']['rows'])){
              //máme nějaké původní vazby => připravíme si seznam nových URIs a případně připravíme query pro odstranění vazby
              foreach ($this->$property as $propertyItem){
                if (isset($propertyItem->uri) && $propertyItem->uri!=''){
                  $relatedUrisArr[]=$propertyItem->uri;
                }
              }
              foreach ($relatedResult['result']['rows'] as $row){
                if (!in_array($row['relatedUri'],$relatedUrisArr)){
                  $queries[]=$queryPrefixes.'DELETE FROM <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entitiesGroup['relation'].' <'.$row['relatedUri'].'>}';
                }
              }
            }
            //uložíme jednotlivé položky a přidáme jejich vazbu...
            foreach ($this->$property as $propertyItem){
              $repository->$entitySaveFunction($propertyItem);
              if ((@$propertyItem->uri!='') && !in_array($propertyItem->uri,$relatedUrisArr)){
                $queries[]=$queryPrefixes.'INSERT INTO <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entitiesGroup['relation'].' <'.$propertyItem->uri.'>}';
              }
            }
          }else{
            //máme odstranit všechny navázané položky
            $queries[]=$queryPrefixes.'DELETE FROM <'.self::BASE_ONTOLOGY.'> {<'.$this->uri.'> '.$entitiesGroup['relation'].' ?relatedUri}';
          }

        }
      }
    }
    #endregion vyřešení navázaných entitiesGroups
    return $queries;
  }


  /**
   * Funkce pro sestavení dotazu pro načtení entity z DB
   * @param string $uri = ''
   * @param string $filter=''
   * @return string
   */
  public static function getLoadQuery($uri='',$filter=''){
    $mappedProperties=self::getMappedProperties();
    $queryPrefixes=self::prepareQueryPrefixes(@$mappedProperties['namespaces']);
    $selectQuery='?uri';
    $whereQuery='. ?uri a '.$mappedProperties['class'];
    if (!empty($mappedProperties['literals']) && is_array($mappedProperties['literals'])){
      foreach ($mappedProperties['literals'] as $literal){
        $property=$literal['property'];
        $selectQuery.=' ?'.$literal['property'];
        if (@$literal['optional']){
          $whereQuery.='. OPTIONAL {?uri '.$literal['relation'].' ?'.$property.'}';
        }else{
          $whereQuery.='. ?uri '.$literal['relation'].' ?'.$property;
        }
      }
    }
    if ($uri!=''){
      $selectQuery=Strings::replace($selectQuery,'/\?uri/','');
      $whereQuery=Strings::replace($whereQuery,'/\?uri/','<'.$uri.'>');
    }
    $query=$queryPrefixes.'SELECT '.$selectQuery.' FROM <'.self::BASE_ONTOLOGY.'> WHERE {'.Strings::substring($whereQuery,2).(!empty($filter)?' . '.$filter.'':'').'}';
    return $query;
  }

  /**
   * Funkce pro připravení entity na základě dat načtených v rámci sparql dotazu
   */
  public function prepareEntity($data,$uri=''){
    $mappedProperties=self::getMappedProperties();
    if (!empty($mappedProperties['literals']) && is_array($mappedProperties['literals'])){
      foreach ($mappedProperties['literals'] as $literal){
        $property=$literal['property'];
        if (isset($data[$property])){
          $this->$property=$data[$property];
        }
      }
    }
    if ($uri!=''){
      $this->uri=$uri;
    }elseif(!empty($data['uri'])){
      $this->uri=$data['uri'];
    }
  }

  /**
   * Funkce pro přípravu definice prefixů pro SPARQL dotaz
   * @param array $prefixesArr
   * @return string
   */
  public static function prepareQueryPrefixes($prefixesArr){
    $query='';
    if (!empty($prefixesArr)){
      foreach ($prefixesArr as $prefix=>$uri){
        $query.="PREFIX ".$prefix.": <".$uri."> . \n";
      }
    }
    return $query;
  }


  public function __set($name,$value){
    $mappedProperties=$this->getMappedProperties();
    if ((!method_exists($this,'set'.ucfirst($name))) && (isset($mappedProperties['entities'][$name]) || isset($mappedProperties['literals'][$name]) || isset($mappedProperties['entitiesGroups'][$name]))){
      $this->$name=$value;
    }else{
      parent::__set($name,$value);
    }
  }


  /**
   * Funkce pro vyescapování speciálních znaků pro použití řetězce ve SPARQL dotazu
   * @param $literal
   * @return string
   */
  public static function escapeSparql($literal){
    return addslashes($literal);
  }

  /**
   * Funkce pro vyescapování a obalení uvozovkami pro úpravu hodnot literálů pro použití ve SPARQL dotazu
   * @param $literal
   * @return string
   */
  public static function quoteSparql($literal){
    return '"'.self::escapeSparql($literal).'"';
  }

  /**
   * Funkce pro připravení základní URI pro danou entitu
   * @return string
   */
  public function prepareBaseUri(){
    $mappedProperties=$this->getMappedProperties();
    $uri=$mappedProperties['class'];
    if (!Strings::endsWith($uri,'/') && !Strings::contains($uri,'#')){
      $uri.='/';
    }
    if (isset($this->name)){
      $uri.=Strings::webalize($this->name);
    }else{
      $uri.='x';
    }
    return $uri;
  }

}