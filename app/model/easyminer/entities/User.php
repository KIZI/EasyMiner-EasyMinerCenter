<?php

namespace App\Model\EasyMiner\Entities;
use App\Libs\StringsHelper;
use LeanMapper\Entity;
use Nette\Utils\DateTime;

/**
 * Class User
 * @package App\Model\EasyMiner\Entities
 * @property int|null $userId
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $facebookId
 * @property string|null $googleId
 * @property DateTime $lastLogin
 * @property bool $active = true
 */
class User extends Entity{
  /**
   * @return string
   */
  public function getDbPassword(){
    return StringsHelper::decodePassword($this->row->db_password);
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    $this->row->db_password=StringsHelper::encodePassword($password);
  }
}