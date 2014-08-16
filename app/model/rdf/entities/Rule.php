<?php

namespace App\Model\Rdf\Entities;
use Nette\Neon\Exception;

/**
 * Class Rule
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property Cedent $antecedent
 * @property Cedent $consequent
 * @property string $text
 * @property string $rating
 * @property RuleSet[] $ruleSets
 * @property KnowledgeBase $knowledgeBase
 *
 * @rdfClass(class='kb:Rule')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$text,relation='kb:hasText',optional=true)
 * @rdfEntity (property=$antecedent,relation='kb:hasAntecedent',entity='Cedent')
 * @rdfEntity(property=$consequent,relation='kb:hasConsequent',entity='Cedent')
 * @rdfLiteral(property=$rating,relation='kb:hasRating',optional=true)
 * @rdfEntitiesGroup (property=$ruleSets,reverseRelation='kb:hasRule',entity='RuleSet')
 * @rdfEntity(property=$knowledgeBase,relation='kb:isInBase',entity='KnowledgeBase')
 */
class Rule extends BaseEntity{

  public function getRatingArr(){
    try{
      return json_decode($this->rating,true);
    }catch (Exception $e){}
    return null;
  }

  #region pracovní metody pro sledování změn
  public function getRating(){
    if (!isset($this->rating)){
      return null;
    }
    return @$this->rating;
  }
  public function setRating($rating){
    if (is_string($rating)){
      $this->rating=$rating;
    }else{
      $this->rating=json_encode($rating);
    }
    $this->setChanged();
  }
  public function setAntecedent(Cedent $antecedent){
    $this->antecedent=$antecedent;
    $this->setChanged();
  }
  public function setConsequent(Cedent $consequent){
    $this->consequent=$consequent;
    $this->setChanged();
  }
  public function setText($text){
    $this->text=$text;
    $this->setChanged();
  }
  #endregion pracovní metody pro sledování změn
} 