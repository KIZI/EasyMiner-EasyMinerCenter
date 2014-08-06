<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 3.7.14
 * Time: 16:15
 */

namespace App\Model\Rdf\Entities;

/**
 * Class Cedent
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $connective = conjunction|disjunction|negation
 * @property Attribute[] $attributes
 *
 * @rdfClass(class="Cedent")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$connective,optional=true,default='conjunction',values=['conjunction','disjunction','negation'])
 * @rdfEntitiesGroup(property=$attributes,relation='hasAttribute',entity='Attribute')
 */
class Cedent extends BaseEntity{

} 