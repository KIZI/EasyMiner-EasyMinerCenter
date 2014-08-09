<?php

namespace App\Model\Rdf\Entities;

/**
 * Class RuleSet
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property Rule[] $rules
 *
 * @rdfClass(class='kb:RuleSet')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=true)
 * @rdfEntitiesGroup(property=$rules,relation='hasRule',entity='Rule')
 */
class RuleSet extends BaseEntity{


} 