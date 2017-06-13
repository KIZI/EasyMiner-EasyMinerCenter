<?php

namespace EasyMinerCenter\Model\Mining\Entities;

/**
 * Class Outlier - class representing one outlier (rating of one data row as outlier)
 * @package EasyMinerCenter\Model\Mining\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class Outlier{
  /** @var  int $id */
  public $id;
  /** @var  float $score */
  public $score;
  /** @var  array $attributeValues - array with values of concrete attributes (the keys are names of attributes, values are values of these attributes) */
  public $attributeValues=[];

}