<?php

namespace App\Libs;


class StringsHelper {

  /**
   * Funkce pro naplnění řetězce pomocí parametrů
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
   * Funkce vracející náhodný řetězec zadané délky
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

} 