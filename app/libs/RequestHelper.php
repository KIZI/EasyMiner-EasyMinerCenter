<?php

namespace EasyMinerCenter\Libs;
use Nette\Http\Url;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class RequestHelper - třída shromažďující funkce pro práci s odesíláním requestů
 * @package EasyMinerCenter\Libs
 */
class RequestHelper {
  const IN_BACKGROUND_RESPONSE='REQUEST IN BACKGROUND';

  /**
   * Funkce pro odeslání background requestu pomocí CURL
   * @param string $url
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest($url){
    Debugger::log($url, 'RequestHelper');

    $ch = curl_init($url);
    //nastavení parametrů připojení včetně timeoutu
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

    //odeslání požadavku na načtení
    $responseData = curl_exec($ch);

    //zjištění info o chybě
    if ($errorCode=$error = curl_errno($ch)){
      $errstr=curl_error($ch);
    }
    curl_close($ch);

    if ($errorCode && $errorCode!=CURLE_OPERATION_TIMEOUTED){
      //jde opravdu o chybu, ne jen o ukončení spojení pro běh na pozadí

      if (empty($errstr)){
        $errstr='Background CURL request error (error: '.$errorCode.')';
      }

      Debugger::log(@$errstr,ILogger::ERROR);
      Debugger::log(@$errstr,'RequestHelper');//FIXME

      throw new \Exception($errstr);
    }
  }

  /**
   * Funkce pro odeslání ukončení info klientovi a ignorování ukončení jeho připojení
   */
  public static function ignoreUserAbort(){
    ignore_user_abort(true);
  }


  const REQUEST_TIMEOUT=5;

  /**
   * Funkce pro odeslání GET požadavku bez čekání na získání odpovědi
   * @param string $url
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest_FSOCKOPEN($url){
    Debugger::log($url,'RequestHelper');//FIXME
    $url = new Url($url);
    $host=$url->getHost();
    if (empty($host)){
      $host='localhost';
    }

    #region parametry připojení
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
    #endregion

    $fp=@fsockopen($scheme.$host, $port, $errno, $errstr, self::REQUEST_TIMEOUT);
    if (!$fp){
      Debugger::log($errstr,ILogger::ERROR);
      Debugger::log($errstr,'RequestHelper');//FIXME
      throw new \Exception($errstr,$errno);
    }
    $path=$url->getPath().($url->getQuery()!=""?'?'.$url->getQuery():'');
    fputs($fp, "GET ".$path." HTTP/1.0\r\nHost: ".$host."\r\n\r\n");
    fputs($fp, "Connection: close\r\n");
    fputs($fp, "\r\n");
  }

}