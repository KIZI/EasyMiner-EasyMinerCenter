<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\IRole;

/**
 * Class OwnerRole - class for management of user privileges - permissions to work with own resources
 * @package EasyMinerCenter\Model\EasyMiner\Authorizators
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class OwnerRole implements IRole{
  private $userId;
  const OWNER_ROLE_ID='owner';

  /**
   * @param int $userId
   */
  public function __construct($userId) {
    $this->userId=$userId;
  }

  /**
   * @return string
   */
  public function getRoleId(){
    return self::OWNER_ROLE_ID;
  }

  /**
   * @return int
   */
  public function getUserId() {
    return $this->userId;
  }

  /**
   * @return string
   */
  public function __toString() {
    return 'owner:'.$this->userId;
  }
}