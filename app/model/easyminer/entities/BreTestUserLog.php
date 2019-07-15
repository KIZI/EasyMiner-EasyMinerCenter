<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;

/**
 * Class BreTestUserLog
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int $breTestUserLogId
 * @property-read \DateTime $created
 * @property int $breTestId
 * @property int $breTestUserId
 * @property string $message = ''
 * @property string $data = ''
 */
class BreTestUserLog extends Entity {

}