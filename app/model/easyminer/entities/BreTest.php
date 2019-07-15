<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class BreTestCase
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $breTestId
 * @property string $name = ''
 * @property string $infoText = ''
 * @property User $user m:hasOne
 * @property Miner $miner m:hasOne
 * @property RuleSet $ruleSet m:hasOne
 * @property Datasource $datasource m:hasOne
 * @property string $testKey = ''
 * @property BreTestUser[] $breTestUsers m:belongsToMany
 * @property-read BreTestUserLog[] $breTestUserLogs m:belongsToMany
 */
class BreTest extends Entity {

  /**
   * @return int
   */
  public function getBreTestUsersCount(){
    return count($this->breTestUsers);
  }

}