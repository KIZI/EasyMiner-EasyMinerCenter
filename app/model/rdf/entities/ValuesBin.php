<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 3.7.14
 * Time: 16:14
 */

namespace App\Model\Rdf\Entities;

/**
 * Class ValuesBin
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $name
 * @property Interval[] $intervals
 * @property Value[] $values
 *
 * @rdfClass(class='kb:ValuesBin')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfEntitiesGroup(property=$intervals,relation='kb:hasInterval',entity='Interval')
 * @rdfEntitiesGroup(property=$values,relation='kb:hasValue',entity='Value')
 */
class ValuesBin extends BaseEntity{
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