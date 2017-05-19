<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use LeanMapper\Entity;

/**
 * Class UserForgottenPassword
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
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