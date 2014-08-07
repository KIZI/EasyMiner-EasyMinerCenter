<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 3.7.14
 * Time: 16:07
 */

namespace App\Model\Rdf\Entities;
//TODO chybí range!!!
/**
 * Class Format
 *
 * @package App\Model\Rdf\Entities
 * @property string $uri
 * @property string $name
 * @property string $dataType
 * @property ValuesBin[] $valuesBins
 * @property Preprocessing[] $preprocessings
 * @property MetaAttribute $metaAttribute
 *
 * @rdfClass(class="kb:Format")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral(property=$name,relation='kb:hasName')
 * @rdfLiteral(property=$dataType,relation='kb:hasDataType')
 * @rdfEntitiesGroup(property=$valuesBins,relation='kb:hasValuesBin',entity='ValuesBin')
 * @rdfEntitiesGroup(property=$preprocessings,relation='kb:hasPreprocessing',entity='Preprocessing')
 * @rdfEntity(property=$metaAttribute,reverseRelation='kb:hasFormat')
 */
class Format  extends BaseEntity{
  //TODO
} 