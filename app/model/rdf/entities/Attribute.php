<?php

namespace App\Model\Rdf\Entities;

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
 *
 * @rdfClass(class="kb:Attribute")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName')
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
} 