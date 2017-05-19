<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class MetaAttribute
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $metaAttributeId
 * @property string $name
 * @property Format[] $formats m:belongsToMany
 * @property KnowledgeBase|null $knowledgeBase m:hasOne
 */
class MetaAttribute extends Entity{


} 