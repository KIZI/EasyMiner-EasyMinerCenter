<?php

namespace EasyMinerCenter\Libs;

/**
 * Class RequestHelper - třída shromažďující funkce pro práci s odesíláním requestů
 * @package EasyMinerCenter\Libs
 */
class RequestHelper {
  const REQUEST_TIMEOUT=5;

  /**
   * Funkce pro odeslání GET požadavku bez čekání na získání odpovědi
   * @param string $url
   * @param string $server="localhost"
   * @param int $port=80
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest($url, $server='localhost', $port=80){
    $fp=fsockopen($server, $port, $errno, $errstr, self::REQUEST_TIMEOUT);
    if (!$fp){
      throw new \Exception($errstr,$errno);
    }
    fwrite($fp, "GET ".$url." HTTP/1.0\r\nHost: ".$server."\r\n\r\n");
    fclose($fp);
  }

}