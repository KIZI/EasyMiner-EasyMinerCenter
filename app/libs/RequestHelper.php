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
  const REQUEST_TIMEOUT=5;

  /**
   * Funkce pro odeslání GET požadavku bez čekání na získání odpovědi
   * @param string $url
   * @throws \Exception
   */
  public static function sendBackgroundGetRequest($url){
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