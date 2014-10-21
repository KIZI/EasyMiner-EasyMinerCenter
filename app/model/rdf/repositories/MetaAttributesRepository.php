<?php
namespace App\Model\Rdf\Repositories;

use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\BaseEntity;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\KnowledgeBase;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Entities\Rule;
use App\Model\Rdf\Entities\RuleSet;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Class KnowledgeRepository
 * @package App\Model\Rdf\Repositories
 * @method saveMetaAttribute(MetaAttribute $metaAttribute,&$urisArr=array())
 * @method MetaAttribute findMetaAttribute($uri)
 * @method MetaAttribute[] findMetaAttributes($params, int $limit=-1, int $offset=-1)
 */
class MetaAttributesRepository extends BaseRepository{

} 