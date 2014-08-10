<?php
namespace App\Model\Rdf\Entities;

/**
 * Class Cedent
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $connective = conjunction|disjunction|negation
 * @property RuleAttribute[] $ruleAttributes
 * @property Cedent[] $cedents
 *
 * @rdfClass(class="Cedent")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$connective,optional=true,default='conjunction',values=['conjunction','disjunction','negation'])
 * @rdfEntitiesGroup(property=$ruleAttributes,relation='hasAttribute',entity='RuleAttribute')
 * @rdfEntitiesGroup(property=$cedents,relation='hasCedent',entity='Cedent')
 */
class Cedent extends BaseEntity{

} 