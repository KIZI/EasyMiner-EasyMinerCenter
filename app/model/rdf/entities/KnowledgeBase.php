<?php

namespace App\Model\Rdf\Entities;

/**
 * Class KnowledgeBase
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 *
 * @rdfClass(class='kb:KnowledgeBase')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=true)
 */
class KnowledgeBase extends BaseEntity{
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