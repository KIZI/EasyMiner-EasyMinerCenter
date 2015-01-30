<?php

namespace App\Model\EasyMiner\Facades;

use App\Model\Data\Entities\DbConnection;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\MetaAttribute;
use App\Model\EasyMiner\Entities\Metasource;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\AttributesRepository;
use App\Model\EasyMiner\Repositories\MetasourcesRepository;
use Nette\Utils\Strings;

class MetaAttributesFacade {
  /** @var MetaAttributesRepository $metaAttributesRepository */
  private $metaAttributesRepository;
  /** @var FormatsRepository $formatsRepository */
  private $formatsRepository;


  /**
   * @param MetaAttributesRepository $metasourcesRepository
   * @param FormatsRepository $formatsRepository
   */
  public function __construct(MetaAttributesRepository $metasourcesRepository, FormatsRepository $formatsRepository){
    $this->metaAttributesRepository=$metasourcesRepository;
    $this->formatsRepository=$formatsRepository;
  }

  /**
   * @param int $id
   * @return Metasource
   */
  public function findMetaAttribute($id) {
    return $this->metaAttributesRepository->find($id);
  }

  /**
   * @param MetaAttribute $metasource
   * @return bool
   */
  public function saveMetasource(MetaAttribute &$metaAttribute) {
    $result = $this->metaAttributesRepository->persist($metaAttribute);
    return $result;
  }


  /**
   * @param MetaAttribute|int $metaAttribute
   * @return int
   */
  public function deleteMetasource($metaAttribute){
    if (!($metaAttribute instanceof Metasource)){
      $metasource=$this->findMetaAttribute($metaAttribute);
    }
    return $this->metaAttributesRepository->delete($metasource);
  }


}