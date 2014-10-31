<?php


namespace App\Model\Rdf\Entities;

/**
 * Class Preprocessing
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property string $specialType
 * @property ValuesBin[] $valuesBins
 * @property Attribute[] $generatedAttributes
 * @property Format $format
 *
 * @rdfClass(class='kb:Preprocessing')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfLiteral(property=$specialType,relation='kb:specialType',optional=true)
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:useValuesBins')
 * @rdfEntitiesGroup(property=$generatedAttributes,reverseRelation='kb:isBasedOn')
 * @rdfEntity(property=$format,reverseRelation='kb:hasPreprocessing')
 */
class Preprocessing extends BaseEntity{

  const SPECIALTYPE_EACHONE='eachOne';

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