<?php

namespace App\Model\Rdf\Entities;

/**
 * Class Rule
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property Cedent $antecedent
 * @property Cedent $consequent
 * @property string $text
 *
 * @rdfClass(class='kb:Rule')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$text,relation='kb:hasText',optional=true)
 * @rdfEntity (property=$antecedent,relation='kb:hasAntecedent',entity='Cedent')
 * @rdfEntity (property=$consequent,relation='kb:hasConsequent',entity='Cedent')
 */
class Rule extends BaseEntity{

} 