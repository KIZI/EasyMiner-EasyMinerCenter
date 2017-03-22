<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use EasyMinerCenter\Model\Mining\Entities\MinerOutliersTask;
use LeanMapper\Entity;

/**
 * Class OutliersTask
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @property int|null $outliersTaskId = null
 * @property Miner $miner m:hasOne
 * @property string $type = 'cloud' m:Enum(self::TYPE_*)
 * @property float $minSupport
 * @property string $state m:Enum(self::STATE_*)
 * @property int|null $minerOutliersTaskId = null
 * @property string $resultsUrl = ''
 * @property \DateTime $updated
 */
class OutliersTask extends Entity{
  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_SOLVED='solved';
  const STATE_FAILED='failed';
  const STATE_INVALID='invalid';

  const TYPE_CLOUD='cloud';

  /**
   * Funkce vracející óbjekt prezentující vzdálenou úlohu miner driveru
   * @return MinerOutliersTask
   */
  public function getMinerOutliersTask(){
    return new MinerOutliersTask($this->minerOutliersTaskId,$this->miner->metasource->ppDatasetId);
  }

  /**
   * Funkce vracející základní data v podobě pole
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->outliersTaskId,
      'minSupport'=>$this->minSupport,
      'state'=>$this->state
    ];
  }

}