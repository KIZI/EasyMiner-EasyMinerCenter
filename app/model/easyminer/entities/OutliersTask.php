<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;

use EasyMinerCenter\Model\Mining\Entities\MinerOutliersTask;
use LeanMapper\Entity;

/**
 * Class OutliersTask
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $outliersTaskId = null
 * @property Miner $miner m:hasOne
 * @property string $type = 'cloud' m:Enum(self::TYPE_*)
 * @property float $minSupport
 * @property string $state = 'new' m:Enum(self::STATE_*)
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
   * Method returning a object representing the remote task of miner driver
   * @return MinerOutliersTask
   */
  public function getMinerOutliersTask(){
    return new MinerOutliersTask($this->minerOutliersTaskId,$this->miner->metasource->ppDatasetId);
  }

  /**
   * Method returning an array with basic data properties
   * @return array
   */
  public function getDataArr(){
    return [
      'id'=>$this->outliersTaskId,
      'minSupport'=>$this->minSupport,
      'state'=>$this->state
    ];
  }

  /**
   * @return OutliersTaskState
   */
  public function getTaskState(){
    return new OutliersTaskState($this,$this->state,$this->resultsUrl);
  }

}