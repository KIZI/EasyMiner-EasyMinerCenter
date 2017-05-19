<?php

namespace EasyMinerCenter\Libs;

use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use Nette\Utils\Strings;

/**
 * Class StringsHelper
 * @package EasyMinerCenter\Libs
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class StringsHelper {

  const PASSWORDS_SALT='zruiopwkjhgfdsayexcvbnqtm';

  /**
   * Method for preparation of a "safe" name for attribute, data field etc.
   * @param $string
   * @return string
   */
  public static function prepareSafeName($string) {
    $string=str_replace('-','_',Strings::webalize($string,true));
    while(strpos($string,'__')>0){
      $string=str_replace('__','_',$string);
    }
    return trim($string,'_');
  }

  /**
   * Method for filling-in of params in a masked string
   * @param string $string
   * @param array $params
   * @return string
   */
  public static function replaceParams($string,array $params){
    if (count($params)>0){
      $arr1=array();
      $arr2=array();
      foreach ($params as $param=>$value){
        $arr1[]='{'.$param.'}';
        $arr1[]=$param;
        $arr2[]=$value;
        $arr2[]=$value;
      }
      return str_replace($arr1,$arr2,$string);
    }
    return $string;
  }

  /**
   * Method returning a random string with the given length
   * @param int $length
   * @return string
   */
  public static function randString($length){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = strlen( $chars );
    $str='';
    for( $i = 0; $i < $length; $i++ ) {
      $str .= $chars[ rand( 0, $size - 1 ) ];
    }
    return $str;
  }

  /**
   * Method for symetric encoding of a password
   * @param string $password
   * @return string
   */
  public static function encodePassword($password){
    $saltLength=Strings::length(self::PASSWORDS_SALT)-1;
    $passwordsSalt=self::PASSWORDS_SALT.self::PASSWORDS_SALT.self::PASSWORDS_SALT;
    $randNumber=rand(1,$saltLength);
    $char=Strings::substring($passwordsSalt,$randNumber,1);//získán náhodný znak ze soli
    $length=12;
    $key=Strings::substring($passwordsSalt,$randNumber,$length);
    $encodedPassword=$char.$length.self::encrypt($password,$key);
    return $encodedPassword;
  }

  /**
   * Method for symetric decoding of a password encoded using encodePassword()
   * @param string $encodedPassword
   * @return string
   */
  public static function decodePassword($encodedPassword){
    $char=Strings::substring($encodedPassword,0,1);
    $length=12;
    $passwordsSalt=self::PASSWORDS_SALT.self::PASSWORDS_SALT.self::PASSWORDS_SALT;
    $randNumber=mb_strpos($passwordsSalt,$char,null,'utf-8');
    $key=Strings::substring($passwordsSalt,$randNumber,$length);
    $encodedPassword=Strings::substring($encodedPassword,3);
    $encodedPassword=self::decrypt($encodedPassword,$key);
    return $encodedPassword;
  }

  /**
   * Method for text encryption
   * @param string $inputString
   * @param string $key
   * @return string
   */
  private static function encrypt($inputString, $key){
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $h_key = hash('sha256', $key, true);
    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $inputString,MCRYPT_MODE_ECB, $iv));
  }

  /**
   * Funkce pro dekódování zašifrovaného textu
   * @param string $encryptedInputString
   * @param string $key
   * @return string
   */
  private static function decrypt($encryptedInputString, $key){
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $h_key = hash('sha256', $key, true);
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encryptedInputString),
      MCRYPT_MODE_ECB, $iv));
  }


  /**
   * Method for preparing a string representation of a interval
   * @param string $leftBound
   * @param float $leftValue
   * @param float $rightValue
   * @param string $rightBound
   * @return string
   */
  public static function formatIntervalString($leftBound,$leftValue,$rightValue,$rightBound){
    $output='';
    if (Strings::lower($leftBound)=='open'){
      $output.='(';
    }else{
      $output.='[';
    }
    $output.=$leftValue.';'.$rightValue;
    if (Strings::lower($rightBound)=='open'){
      $output.=')';
    }else{
      $output.=']';
    }
    return $output;
  }

} 