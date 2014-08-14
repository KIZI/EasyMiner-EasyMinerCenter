<?php

namespace App\Model\Rdf\Entities;

/**
 * Class Rule
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property Cedent $antecedent
 * @property Cedent $consequent
 * @property string $text
 * @property string|array $rating
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

  public function getRating(){
    return '';//TODO rating
    if ($result=json_decode($this->rating,true)){
      return $result;
    }else{
      $this->setRating(array());
      return $this->getRating();
    }
  }

  public function setRating($ratingArr){
    if (is_array($ratingArr) || is_object($ratingArr)){
      $this->rating=json_encode($ratingArr);
    }elseif(is_string($ratingArr)){
      $this->rating=$ratingArr;
    }else{
      $this->setRating(array());
    }
    $this->setChanged(true);
  }

  #region pracovní metody pro sledování změn
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