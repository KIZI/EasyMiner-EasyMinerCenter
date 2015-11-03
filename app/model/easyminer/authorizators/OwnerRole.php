<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\IRole;

/**
 * Class OwnerRole - třída pro správu oprávnění k vlastním zdrojům uživatelů
 * @package EasyMinerCenter\Model\EasyMiner\Authorizators;
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