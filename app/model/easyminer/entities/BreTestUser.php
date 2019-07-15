<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class BreTestUser
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $breTestUserId
 * @property RuleSet $ruleSet m:hasOne
 * @property BreTest $breTest m:hasOne
 * @property string $testKey
 * @property \DateTime $created
 */
class BreTestUser extends Entity {

}