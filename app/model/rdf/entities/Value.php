<?php

namespace App\Model\Rdf\Entities;

/**
 * Class Value
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $value
 *
 * @rdfClass(class='kb:Value')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$value,relation='kb:hasValue',optional=false)
 */
class Value extends BaseEntity{

  public function __toString(){
    return $this->value;
  }
  /**
   * Funkce vracející základ pro novou uri (při ukládání nové entity)
   * @return string
   */
  public function prepareBaseUriSeoPart(){
    if (!empty($this->value)){
      return $this->value;
    }else{
      return parent::prepareBaseUriSeoPart();
    }
  }

  #region pracovní metody pro sledování změn
  public function setValue($value){
    $this->value=$value;
    $this->setChanged();
  }
  #endregion pracovní metody pro sledování změn
} 