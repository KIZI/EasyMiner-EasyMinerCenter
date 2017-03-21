<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use LeanMapper\Entity;

/**
 * Class OutliersTask
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @property int|null $outliersTaskId = null
 * @property Metasource $metasource
 * @property float $minSupport
 * @property string $state m:Enum(self::STATE_*)
 * @property int|null $minetTaskId = null
 * @property string $resultsUrl = ''
 * @property \DateTime $updated
 */
class OutliersTask extends Entity{
  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_SOLVED='solved';
  const STATE_FAILED='failed';
  const STATE_INVALID='invalid';

}