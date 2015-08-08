<?php

namespace EasyMinerCenter\Model\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;

/**
 * Class IPreprocessingDriver - rozhraní pro unifikaci práce s dataminingovými nástroji
 * @package EasyMinerCenter\Model\mining
 */
interface IPreprocessingDriver {

  public function generateAttribute(Attribute $attribute);
  
  
} 