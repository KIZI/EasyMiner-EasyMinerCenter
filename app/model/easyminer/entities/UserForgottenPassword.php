<?php

namespace App\Model\EasyMiner\Entities;
use App\Libs\StringsHelper;
use LeanMapper\Entity;

/**
 * Class UserForgottenPassword
 * @package App\Model\EasyMiner\Entities
 * @property int|null $userForgottenPasswordId
 * @property User $user m:hasOne
 * @property string $code
 * @property \DateTime $generated
 */
class UserForgottenPassword extends Entity{
  /**
   * @return string
   */
  public function getCode(){
    if (empty($this->row->code)){return null;}
    return StringsHelper::decodePassword($this->row->code);
  }

  /**
   * @param string $code
   */
  public function setCode($code){
    $this->row->code=StringsHelper::encodePassword($code);
  }
}