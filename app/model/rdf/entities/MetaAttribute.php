<?php

namespace App\Model\Rdf\Entities;

/**
 * Class MetaAttribute
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property-read Format[] $formats
 *
 * @rdfClass(class='kb:MetaAttribute')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfEntitiesGroup(property=$formats,relation='kb:hasFormat')
 */
class MetaAttribute extends BaseEntity{

} 