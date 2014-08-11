<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 5.7.14
 * Time: 15:49
 */

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

} 