<?php

namespace App\Model\Rdf\Entities;

/**
 * Class Attribute
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $name
 * @property Format $format
 * @property ValuesBin[] $valuesBins
 * @property Preprocessing $preprocessing
 *
 * @rdfClass(class="kb:Attribute")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName')
 * @rdfEntity (property=$format,relation='kb:hasFormat',entity='Format')
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:hasValuesBin',entity='ValuesBin')
 * @rdfEntity(property=$preprocessing,relation='kb:isBasedOn')
 */
class Attribute extends BaseEntity{

} 