<?php

namespace EasyMinerCenter\Libs;
use Nette\Http\Url;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class RequestHelper - class with methods for sending of requests
 * @package EasyMinerCenter\Libs
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RequestHelper {
  const IN_BACKGROUND_RESPONSE='REQUEST IN BACKGROUND';

  /**
   * Method for senging a CURL request in background
   * @param string $url
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest($url){
    $ch = curl_init($url);
    //set connection params (including timeout)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_MAXREDIRS,5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_VERBOSE, false);

    //execute the CURL request
    curl_exec($ch);

    //get errorCode
    if ($errorCode=$error = curl_errno($ch)){
      $errstr=curl_error($ch);
    }
    curl_close($ch);

    if ($errorCode && $errorCode!=CURLE_OPERATION_TIMEOUTED){
      //it is really an error, not only run on background

      if (empty($errstr)){
        $errstr='Background CURL request error (error: '.$errorCode.')';
      }

      Debugger::log(@$errstr,ILogger::ERROR);

      throw new \Exception($errstr);
    }
  }

  /**
   * Method for sending a response to the user and ignoring the disconnection of the client from server
   */
  public static function ignoreUserAbort(){
    ignore_user_abort(true);
  }


  const REQUEST_TIMEOUT=5;

  /**
   * Method for sending a GET request without waiting for response - via FSOSKOPEN
   * @param string $url
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest_FSOCKOPEN($url){
    $url = new Url($url);
    $host=$url->getHost();
    if (empty($host)){
      $host='localhost';
    }

    #region connection params
    switch ($url->getScheme()) {
      case 'https':
        $scheme = 'ssl://';
        $port = 443;
        break;
      case 'http':
      default:
        $scheme = '';
        $port = 80;
    }
    $urlPort=$url->getPort();
    if (!empty($urlPort)) {
      $port=$urlPort;
    }
    #endregion connection params

    $fp=@fsockopen($scheme.$host, $port, $errno, $errstr, self::REQUEST_TIMEOUT);
    if (!$fp){
      Debugger::log($errstr,ILogger::ERROR);
      throw new \Exception($errstr,$errno);
    }
    $path=$url->getPath().($url->getQuery()!=""?'?'.$url->getQuery():'');
    fputs($fp, "GET ".$path." HTTP/1.0\r\nHost: ".$host."\r\n\r\n");
    fputs($fp, "Connection: close\r\n");
    fputs($fp, "\r\n");
  }

}