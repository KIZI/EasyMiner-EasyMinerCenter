<?php

namespace App\Model\Preprocessing;
use App\Model\EasyMiner\Entities\Attribute;

/**
 * Class IPreprocessingDriver - rozhraní pro unifikaci práce s dataminingovými nástroji
 * @package App\Model\mining
 */
interface IPreprocessingDriver {

  public function generateAttribute(Attribute $attribute);
  
  
} 