<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 5.7.14
 * Time: 15:36
 */

namespace App\Model\Rdf\Entities;

/**
 * Class RuleAttribute
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property Attribute $attribute
 * @property ValuesBin[] $valuesBins
 *
 * @rdfClass(class="kb:RuleAttribute")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfEntity (property=$attribute,relation='kb:isAttribute',entity='Attribute')
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:hasValuesBin',entity='ValuesBin')
 */
class RuleAttribute extends BaseEntity{
  #region pracovní metody pro sledování změn
  public function setAttribute(Attribute $attribute){
    $this->attribute=$attribute;
    $this->setChanged();
  }
  #endregion pracovní metody pro sledování změn
} 