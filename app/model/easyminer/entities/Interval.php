<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class Interval
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property Format $format m:hasOne
 * @property float $leftMargin
 * @property float $rightMargin
 * @property string $leftClosure  m:Enum(self::CLOSURE_*)
 * @property string $rightClosure m:Enum(self::CLOSURE_*)
 */
class Interval extends Entity{
  const CLOSURE_OPEN='open';
  const CLOSURE_CLOSED='closed';

} 