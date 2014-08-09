<?php

namespace App\Model\Rdf\Entities;

/**
 * Class KnowledgeBase
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 *
 * @rdfClass(class='kb:KnowledgeBase')
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName',optional=true)
 */
class KnowledgeBase extends BaseEntity{


} 