<?php

namespace App\Model\Rdf\Entities;

/**
 * Class RuleSet
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property Rule[] $rules
 * @property KnowledgeBase $knowledgeBase
 *
 * @rdfClass(class='kb:RuleSet')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=true)
 * @rdfEntitiesGroup(property=$rules,relation='kb:hasRule',entity='Rule')
 * @rdfEntity(property=$knowledgeBase,relation='kb:isInBase',entity='KnowledgeBase')
 */
class RuleSet extends BaseEntity{
  /**
   * Funkce vracející základ pro novou uri (při ukládání nové entity)
   * @return string
   */
  public function prepareBaseUriSeoPart(){
    if (!empty($this->name)){
      return $this->name;
    }else{
      return parent::prepareBaseUriSeoPart();
    }
  }

  #region pracovní metody pro sledování změn
  public function setName($name){
    $this->name=$name;
    $this->setChanged();
  }
  #endregion pracovní metody pro sledování změn

} 