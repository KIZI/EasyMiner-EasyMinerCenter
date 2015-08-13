<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use LeanMapper\Entity;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Class User
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $userId
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $facebookId
 * @property string|null $googleId
 * @property string $apiKey
 * @property DateTime $lastLogin
 * @property bool $active = true
 * @property-read string $encodedApiKey
 */
class User extends Entity{
  /**
   * @return string
   */
  public function getDbPassword(){
    if (empty($this->row->db_password)){return null;}
    return StringsHelper::decodePassword($this->row->db_password);
  }

  /**
   * @param string $password
   */
  public function setDbPassword($password){
    $this->row->db_password=StringsHelper::encodePassword($password);
  }

  /**
   * Funkce vracející zakódovaný API KEY pro tohoto uživatele
   * @return null|string
   */
  public function getEncodedApiKey(){
    if ($this->apiKey!='' && $this->userId){
      return self::encodeUserApiKey($this->userId,$this->apiKey);
    }
    return null;
  }


  /**
   * Funkce pro zakódování userId a apiKey do jednoho řetězce
   * @param string|int $userId
   * @param string $apiKey
   * @return string
   */
  public static function encodeUserApiKey($userId,$apiKey){
    $output=Strings::substring($apiKey,0,3);
    $number=(intval(str_replace([".",":"],["",""],$_SERVER['SERVER_ADDR']))+15)%15+1;//příprava random čísla dle IP adresy serveru
    $output.=dechex($number);
    $output.=Strings::substring($apiKey,3,$number);
    $output.=($userId+18039);
    $output.=Strings::substring($apiKey,3+$number);
    return $output;
  }

  /**
   * Funkce pro dekódování encodedApiKey na pole s userId a apiKey
   * @param string $encodedApiKey
   * @return array
   */
  public static function decodeUserApiKey($encodedApiKey){
    var_dump($encodedApiKey);
    $realKey=Strings::substring($encodedApiKey,0,3);
    $number=hexdec($encodedApiKey[3]);
    $realKey.=Strings::substring($encodedApiKey,4,$number).Strings::substring($encodedApiKey,4+$number+5);
    $userId=Strings::substring($encodedApiKey,4+$number,5);
    $userId-=18039;
    return ['userId'=>$userId,'apiKey'=>$realKey];
  }

}