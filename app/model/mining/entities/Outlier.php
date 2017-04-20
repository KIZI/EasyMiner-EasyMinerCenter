<?php

namespace EasyMinerCenter\Model\Mining\Entities;

/**
 * Class Outlier - třída pro záznam jednoho outlierů (ohodnocení jedné datové řádky jako outlieru)
 * @package EasyMinerCenter\Model\Mining\Entities
 * @author Stanislav Vojíř
 */
class Outlier{
  /** @var  int $id */
  public $id;
  /** @var  float $score */
  public $score;
  /** @var  array $attributeValues - pole s hodnotami konkrétních atributů (klíčem jsou jména atributů, hodnotami pak hodnoty daných atributů)*/
  public $attributeValues=[];

}