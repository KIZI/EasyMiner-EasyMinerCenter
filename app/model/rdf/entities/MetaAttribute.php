<?php

namespace App\Model\Rdf\Entities;

/**
 * Class MetaAttribute
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property Format[] $formats
 * @property KnowledgeBase $knowledgeBase
 *
 * @rdfClass(class='kb:MetaAttribute')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfEntitiesGroup(property=$formats,relation='kb:hasFormat',entity='Format')
 * @rdfEntity(property=$knowledgeBase,relation='kb:isInBase',entity='KnowledgeBase')
 */
class MetaAttribute extends BaseEntity{
  /**
   * Funkce vracející základ pro novou uri (při ukládání nové entity)
   * @return string
   */
  public function prepareBaseUriSeoPart(){
    if ($this->name){
      return $this->name;
    }else{
      return parent::prepareBaseUriSeoPart();
    }
  }
} 