<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class MetaAttribute
 * @property int $metaAttributeId
 * @property string $name
 * @property Format[] $formats m:belongsToMany
 * @property KnowledgeBase|null $knowledgeBase m:hasOne
 */
class MetaAttribute extends Entity{


} 