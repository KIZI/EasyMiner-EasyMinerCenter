<?php


namespace App\Model\Rdf\Entities;

/**
 * Class Preprocessing
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property ValuesBin[] $valuesBins
 * @property Attribute[] $generatedAttributes
 *
 * @rdfClass(class='kb:Preprocessing')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:useValuesBins')
 * @rdfEntitiesGroup(property=$generatedAttributes,reverseRelation='kb:isBasedOn')
 */
class Preprocessing {

} 