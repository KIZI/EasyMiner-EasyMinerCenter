<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class MetaAttribute
 * @property int $metaAttributeId
 * @property string $name
 * @property Format[] $formats m:belongsToMany
 * @property KnowledgeBase $knowledgeBase
 */
class MetaAttribute extends Entity{


} 