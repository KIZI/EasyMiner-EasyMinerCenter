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

} 