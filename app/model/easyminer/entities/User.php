<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use LeanMapper\Entity;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

/**
 * Class User
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $userId
 * @property string $name
 * @property string $email
 * @property string $password = ''
 * @property string|null $facebookId = ''
 * @property string|null $googleId = ''
 * @property string $apiKey = ''
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
    /** @noinspection PhpUndefinedFieldInspection */
    $this->row->db_password=StringsHelper::encodePassword($password);
  }

  /**
   * Funkce pro nastavení timestampu poslední kontroly přístupu do DB
   * @param string $dbType
   * @param int $timestamp
   */
  public function setLastDbCheck($dbType,$timestamp){
    $data=$this->getLastDbCheck();
    $data[$dbType]=$timestamp;
    try{
      /** @noinspection PhpUndefinedFieldInspection */
      $this->row->last_db_check=Json::encode($data);
    }catch(JsonException $e){
      /** @noinspection PhpUndefinedFieldInspection */
      $this->row->last_db_check='';
    }
  }

  /**
   * Funkce pro zjištění timestampu poslední kontroly přístupu do DB
   * @param null|string $dbType
   * @return int|array
   */
  public function getLastDbCheck($dbType=null){
    if (empty($this->row->last_db_check)){
      $data=[];
    }else{
      try{
        /** @noinspection PhpUndefinedFieldInspection */
        $data=Json::decode($this->row->last_db_check,Json::FORCE_ARRAY);
      }catch(JsonException $e){
        $data=[];
      }
    }

    if ($dbType){
      return intval(@$data[$dbType]);
    }else{
      return $data;
    }
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
    $number=(ord((!empty($apiKey[5])?$apiKey[5]:'?'))+15)%15+1;
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
    $realKey=Strings::substring($encodedApiKey,0,3);
    $number=hexdec(@$encodedApiKey[3]);
    $realKey.=Strings::substring($encodedApiKey,4,$number).Strings::substring($encodedApiKey,4+$number+5);
    $userId=Strings::substring($encodedApiKey,4+$number,5);
    $userId-=18039;
    return ['userId'=>$userId,'apiKey'=>$realKey];
  }
}