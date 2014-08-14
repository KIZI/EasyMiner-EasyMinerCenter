<?php

namespace App\Model\Rdf\Entities;
//TODO property column je pouze pro dočasnou integraci se stávající verzí EasyMineru!!!
/**
 * Class Attribute
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $name
 * @property Format $format
 * @property ValuesBin[] $valuesBins
 * @property Preprocessing $preprocessing
 * @property KnowledgeBase $knowledgeBase
 * @property string $dbColumn
 *
 * @rdfClass(class="kb:Attribute")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName')
 * @rdfLiteral(property=$dbColumn,relation='kb:isDbColumn',optional=true)
 * @rdfEntity (property=$format,relation='kb:hasFormat',entity='Format')
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:hasValuesBin',entity='ValuesBin')
 * @rdfEntity(property=$preprocessing,relation='kb:isBasedOn')
 * @rdfEntity(property=$knowledgeBase,relation='kb:isInBase',entity='KnowledgeBase')
 */
class Attribute extends BaseEntity{
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
  public function setFormat(Format $format){
    $this->format=$format;
    $this->setChanged();
  }
  #endregion pracovní metody pro sledování změn
} 