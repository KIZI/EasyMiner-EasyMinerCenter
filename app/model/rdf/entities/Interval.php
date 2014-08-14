<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 3.7.14
 * Time: 16:15
 */

namespace App\Model\Rdf\Entities;

/**
 * Class Interval
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property Value $leftMargin
 * @property Value $rightMargin
 * @property IntervalClosure $closure
 *
 * @rdfClass(class="kb:Interval")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfEntity (property=$closure,relation='kb:hasClosure',entity='IntervalClosure')
 * @rdfEntity (property=$leftMargin,relation='kb:hasLeftMargin',entity='Value')
 * @rdfEntity (property=$rightMargin,relation='kb:hasRightMargin',entity='Value')
 */
class Interval extends BaseEntity{
  /**
   * Funkce vracející základ pro novou uri (při ukládání nové entity)
   * @return string
   */
  public function prepareBaseUriSeoPart(){
    if ($this->leftMargin || $this->rightMargin){
      return '_'.@$this->leftMargin->value.'-'.@$this->rightMargin->value.'_';
    }else{
      return parent::prepareBaseUriSeoPart();
    }
  }
} 