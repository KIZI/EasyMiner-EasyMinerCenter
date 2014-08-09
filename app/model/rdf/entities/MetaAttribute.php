<?php

namespace App\Model\Rdf\Entities;

/**
 * Class MetaAttribute
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property Interval[] $intervals
 * @property Value[] $values
 * @property Format[] $formats
 * @property KnowledgeBase $knowledgeBase
 *
 * @rdfClass(class='kb:MetaAttribute')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=false)
 * @rdfEntitiesGroup(property=$formats,relation='kb:hasFormat')
 * @rdfEntitiesGroup(property=$intervals,relation='kb:hasInterval')
 * @rdfEntitiesGroup(property=$values,relation='kb:hasValue')
 * @rdfEntity(property=$knowledgeBase,relation='kb:isInBase',entity='KnowledgeBase')
 */
class MetaAttribute extends BaseEntity{

} 