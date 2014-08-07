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
 * @property Cedent[] $cedents
 *
 * @rdfClass(class="Cedent")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$connective,optional=true,default='conjunction',values=['conjunction','disjunction','negation'])
 * @rdfEntitiesGroup(property=$attributes,relation='hasAttribute',entity='Attribute')
 * @rdfEntitiesGroup(property=$cedents,relation='hasCedent',entity='Cedent')
 */
class Cedent extends BaseEntity{

} 