<?php

namespace EasyMinerCenter\InstallModule\Model;
use Nette\Utils\Json;

/**
 * Class PhpConfigManager - část modelu pro kontrolu jednotlivých PHP nastavení
 *
 * @package EasyMinerCenter\InstallModule\Model
 */
class PhpConfigManager {

  const TEST_TYPE_ENVIRONMENT='environment';
  const TEST_TYPE_EXTENSIONS='extensions';
  const TEST_TYPE_FUNCTIONS='functions';
  const TEST_TYPE_VARIABLES='variables';

  const STATE_ALL_OK='all_ok';
  const STATE_REQUIRED_OK='required_ok';
  const STATE_ERRORS='errors';

  #region kontrola verze PHP
  /**
   * Funkce vracející minimální požadovanou verzi PHP
   * @return string
   */
  public static function getPhpMinVersion() {
    try{
      $composerConfig=Json::decode(file_get_contents(FilesManager::getRootDirectory().'/composer.json'),Json::FORCE_ARRAY);
      $phpVersion=$composerConfig['require']['php'];
      $phpVersion=ltrim($phpVersion,'>=~ ');
    }catch (\Exception $e){/*chybu ignorujeme...*/}
    if (empty($phpVersion)){
      $phpVersion='5.3.1';
    }
    return $phpVersion;
  }

  /**
   * Funkce pro kontrolu, jestli skript běží na minimální verzi PHP
   * @param string|null $requestedMinPhpVersion=null
   * @return bool
   */
  public static function checkPhpMinVersion($requestedMinPhpVersion=null) {
    if (empty($requestedMinPhpVersion)){$requestedMinPhpVersion=self::getPhpMinVersion();}
    return version_compare(PHP_VERSION,$requestedMinPhpVersion,'>=');
  }
  #endregion kontrola verze PHP


  /**
   * Funkce pro kontrolu splnění konfiguračních požadavků Nette
   * @return array
   */
  private static function getNetteRequirementsResultsArr() {
    $phpMinVersion=self::getPhpMinVersion();
    $tests=[];
    $tests[] = [
      'title' => 'PHP version',
      'type' => self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => self::checkPhpMinVersion($phpMinVersion),
      'message' => $phpMinVersion,
      'description' => 'Your PHP version is too old. Nette Framework requires at least PHP '.$phpMinVersion.' or higher.',
    ];
    #region functions
    $tests[] = [
      'title' => 'Function ini_set()',
      'type' => self::TEST_TYPE_FUNCTIONS,
      'required' => false,
      'passed' => function_exists('ini_set'),
      'message' => 'Allowed',
      'errorMessage' => 'Disabled',
      'description' => 'Function <code>ini_set()</code> is disabled. Some parts of this application may not work properly!',
    ];
    $tests[] = [
      'title' => 'Function error_reporting()',
      'type' => self::TEST_TYPE_FUNCTIONS,
      'required' => true,
      'passed' => function_exists('error_reporting'),
      'description' => 'Function <code>error_reporting()</code> is disabled. This function has to be enabled.',
    ];
    $tests[] = [
      'title' => 'Function flock()',
      'type' => self::TEST_TYPE_FUNCTIONS,
      'required' => true,
      'passed' => flock(fopen(__FILE__, 'r'), LOCK_SH),
      'description' => 'Function <code>flock()</code> is not supported on this filesystem. This function is required for processing of atomic file operations.',
    ];
    #endregion functions
    #region environment
    $tests[] = [
      'title' => 'Register_globals',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => !self::getIniFlag('register_globals'),
      'message' => 'Disabled',
      'errorMessage' => 'Enabled',
      'description' => 'Configuration directive <code>register_globals</code> is enabled. EasyMinerCenter requires this to be disabled.',
    ];
    $tests[] = [
      'title' => 'Variables_order',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'), 'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
      'description' => 'Configuration directive <code>variables_order</code> is missing. It has to be set.',
    ];
    $reflection = new \ReflectionClass(__CLASS__);
    $tests[] = [
      'title' => 'Reflection phpDoc',
      'type' => self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => strpos($reflection->getDocComment(), 'PhpConfigManager') !== FALSE,
      'description' => 'Reflection phpDoc are not available (probably due to an eAccelerator bug). This function is required!',
    ];
    #endregion environment
    #region extensions
    $tests[] = [
      'title' => 'PCRE with UTF-8 support',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => @preg_match('/pcre/u', 'pcre'),
      'description' => 'PCRE extension must support UTF-8.',
    ];
    $tests[] = [
      'title' => 'ICONV extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
      'message' => 'Enabled and works properly',
      'errorMessage' => 'Disabled or does not work properly',
      'description' => 'ICONV extension is required and must work properly.',
    ];
    $tests[] = [
      'title' => 'JSON extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('json'),
      'description' => 'JSON extension is required.',
    ];
    $tests[] = [
      'title' => 'PHP tokenizer',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('tokenizer'),
      'description' => 'PHP tokenizer is required.',
    ];
    $pdo = extension_loaded('pdo') && \PDO::getAvailableDrivers();
    $mysqlPdoAllowed=false;
    if ($pdo){
      $driversArr=\PDO::getAvailableDrivers();
      if (!empty($driversArr)){
        foreach($driversArr as $driver){
          $driver=strtolower($driver);
          if ($driver=="mysql"||$driver=="mysqli"){
            $mysqlPdoAllowed=true;
            break;
          }
        }
      }
    }
    $tests[] = [
      'title' => 'PDO extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => $pdo && $mysqlPdoAllowed,
      'message' => $pdo ? 'Available drivers: ' . implode(' ', \PDO::getAvailableDrivers()) : null,
      'description' => 'PDO extension or PDO drivers are absent or MySQL driver is not configured.',
    ];
    $tests[] = [
      'title' => 'Multibyte String extension',
      'type' => self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('mbstring'),
      'description' => 'Multibyte String extension is required.',
    ];
    $tests[] = [
      'title' => 'MCrypt extension',
      'type' => self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('mcrypt') && function_exists('mcrypt_get_iv_size'),
      'description' => 'MCrypt extension is required.',
    ];/*
    $tests[] = [
      'title' => 'Internalization (INTL) extension',
      'type' => self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('intl'),
      'description' => 'INTL extension is required.',
    ];*/
    $tests[] = [
      'title' => 'Multibyte String function overloading',
      'type' => self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
      'message' => 'Disabled',
      'errorMessage' => 'Enabled',
      'description' => 'Multibyte String function overloading is enabled. If it is enabled, some string function may not work properly.',
    ];
    #endregion extensions
    /* AKTUÁLNĚ NEPOUŽÍVANÉ KONTROLY (funkce, které aplikace aktuálně nepoužívá)
    $tests[] = [
      'title' => 'Memcache extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => FALSE,
      'passed' => extension_loaded('memcache'),
      'description' => 'Memcache extension is absent. You will not be able to use <code>Nette\Caching\Storages\MemcachedStorage</code>.',
    ];

    $tests[] = [
      'title' => 'GD extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => FALSE,
      'passed' => extension_loaded('gd'),
      'description' => 'GD extension is absent. You will not be able to use <code>Nette\Image</code>.',
    ];

    $tests[] = [
      'title' => 'Bundled GD extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => FALSE,
      'passed' => extension_loaded('gd') && GD_BUNDLED,
      'description' => 'Bundled GD extension is absent. You will not be able to use some functions such as <code>Nette\Image::filter()</code> or <code>Nette\Image::rotate()</code>.',
    ];

    $tests[] = array(
      'title' => 'Fileinfo extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => FALSE,
      'passed' => extension_loaded('fileinfo'),
      'description' => 'Fileinfo extension is absent.',
    );

    $tests[] = array(
      'title' => 'Fileinfo extension or mime_content_type()',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => FALSE,
      'passed' => extension_loaded('fileinfo') || function_exists('mime_content_type'),
      'description' => 'Fileinfo extension or function <code>mime_content_type()</code> are absent. You will not be able to determine mime type of uploaded files.',
    );*/
    #region variables
    $tests[] = [
      'title' => 'HTTP_HOST or SERVER_NAME',
      'type'=>self::TEST_TYPE_VARIABLES,
      'required' => true,
      'passed' => isset($_SERVER['HTTP_HOST']) || isset($_SERVER['SERVER_NAME']),
      'message' => 'Present',
      'errorMessage' => 'Absent',
      'description' => 'Either <code>$_SERVER["HTTP_HOST"]</code> or <code>$_SERVER["SERVER_NAME"]</code> must be available for resolving host name.',
    ];
    $tests[] = [
      'title' => 'REQUEST_URI or ORIG_PATH_INFO',
      'type'=>self::TEST_TYPE_VARIABLES,
      'required' => true,
      'passed' => isset($_SERVER['REQUEST_URI']) || isset($_SERVER['ORIG_PATH_INFO']),
      'message' => 'Present',
      'errorMessage' => 'Absent',
      'description' => 'Either <code>$_SERVER["REQUEST_URI"]</code> or <code>$_SERVER["ORIG_PATH_INFO"]</code> must be available for resolving request URL.',
    ];
    $tests[] = [
      'title' => 'SCRIPT_NAME or DOCUMENT_ROOT & SCRIPT_FILENAME',
      'type' => self::TEST_TYPE_VARIABLES,
      'required' => true,
      'passed' => isset($_SERVER['SCRIPT_NAME']) || isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME']),
      'message' => 'Present',
      'errorMessage' => 'Absent',
      'description' => '<code>$_SERVER["SCRIPT_NAME"]</code> or <code>$_SERVER["DOCUMENT_ROOT"]</code> with <code>$_SERVER["SCRIPT_FILENAME"]</code> must be available for resolving script file path.',
    ];
    $tests[] = [
      'title' => 'REMOTE_ADDR or php_uname("n")',
      'type' => self::TEST_TYPE_VARIABLES,
      'required' => true,
      'passed' => isset($_SERVER['REMOTE_ADDR']) || function_exists('php_uname'),
      'message' => 'Present',
      'errorMessage' => 'Absent',
      'description' => '<code>$_SERVER["REMOTE_ADDR"]</code> or <code>php_uname("n")</code> must be available for detecting development / production mode.',
    ];
    #endregion variables
    return $tests;
  }

  /**
   * Funkce pro kontrolu splnění konfiguračních požadavků EasyMinerCenter (navazuje na konfigurační požadavky nette)
   * @return array
   */
  private static function getApplicationRequirementsResultsArr() {
    $tests=[];
    $tests[] = [
      'title' => 'XSL extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('xsl'),
      'description' => 'XSL extension is required.',
    ];
    $tests[] = [
      'title' => 'Sockets extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('sockets'),
      'description' => 'SOCKETS extension is required.',
    ];
    $tests[] = [
      'title' => 'CURL extension',
      'type'=>self::TEST_TYPE_EXTENSIONS,
      'required' => true,
      'passed' => extension_loaded('curl'),
      'description' => 'CURL extension is required.',
    ];
    $tests[] = [
      'title' => 'FOpen Wrapper',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => self::getIniFlag('allow_url_fopen'),
      'message' => 'Enabled',
      'errorMessage' => 'Disabled',
      'description' => 'EasyMinerCenter requires <code>fopen wrapper</code> to be enabled.',
    ];
    $tests[] = [
      'title' => 'Memory limit',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => self::convertPHPSizeToBytes(ini_get('memory_limit'))>=self::convertPHPSizeToBytes('128M'),
      'message' => ini_get('memory_limit'),
      'description' => 'EasyMinerCenter requires memory limit at least 128MB, optimal value is 256MB. The higher value are required for processing of big data sets.',
    ];
    $tests[] = [
      'title' => 'Max execution time',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'message' => ini_get('max_execution_time'),
      'passed' => ini_get('max_execution_time')>=120,
      'description' => 'Allowed <code>max_execution_time</code> has to be 120s or higher.',
    ];
    $tests[] = [
      'title' => 'Function set_time_limit',
      'type'=>self::TEST_TYPE_FUNCTIONS,
      'required' => true,
      'passed' => function_exists('set_time_limit') && (set_time_limit(60)!==false),
      'message' => 'OK',
      'errorMessage' => 'Error',
      'description' => 'Function <code>set_time_limit</code> has to be enabled and working.',
    ];
    $tests[] = [
      'title' => 'Max upload file size',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => false,
      'passed' => self::convertPHPSizeToBytes(ini_get('upload_max_filesize'))>=self::convertPHPSizeToBytes('10M'),
      'message' => ini_get('upload_max_filesize'),
      'description' => 'Directive <code>upload_max_filesize</code> should be set to 10MB or higher.',
    ];
    $tests[] = [
      'title' => 'Max POST size',
      'type'=>self::TEST_TYPE_ENVIRONMENT,
      'required' => true,
      'passed' => self::convertPHPSizeToBytes(ini_get('post_max_size'))>=self::convertPHPSizeToBytes(ini_get('upload_max_filesize')),
      'message' => 'OK',
      'errorMessage'=>ini_get('post_max_size'),
      'description' => 'Value of the directive <code>post_max_size</code> has to be the same or higher than <code>upload_max_filesize</code>.',
    ];
    return $tests;
  }

  /**
   * Funkce pro kompletní otestování konfiguračních požadavků aplikace
   * @return array
   */
  public static function getTestResultsArr() {
    $resultsArr=[];
    $netteResultsArr=self::getNetteRequirementsResultsArr();
    foreach($netteResultsArr as $resultItem){
      $type=$resultItem['type'];
      unset($resultItem['type']);
      $resultsArr[$type][]=$resultItem;
    }
    $applicationResultsArr=self::getApplicationRequirementsResultsArr();
    foreach($applicationResultsArr as $resultItem){
      $type=$resultItem['type'];
      unset($resultItem['type']);
      $resultsArr[$type][]=$resultItem;
    }
    return $resultsArr;
  }

  /**
   * Funkce pro kontrolu, jestli jsou splněny všechny požadavky
   * @param $resultsArr
   * @return string (self::STATE_*)
   */
  public static function checkTestResultsArrState($resultsArr) {
    $result=self::STATE_ALL_OK;
    foreach($resultsArr as $subResultsArr){
      foreach($subResultsArr as $resultItem){
        if (!$resultItem['passed']){
          if (@$resultItem['required']){
            return self::STATE_ERRORS;
          }else{
            $result=self::STATE_REQUIRED_OK;
          }
        }
      }
    }
    return $result;
  }


  /**
   * Gets the boolean value of a configuration option.
   * @param  string  $var - configuration option name
   * @return bool
   */
  public static function getIniFlag($var) {
      $status = strtolower(ini_get($var));
      return $status === 'on' || $status === 'true' || $status === 'yes' || (int) $status;
  }



  /**
   * Funkce pro konverzi velikosti paměti udávané v PHP na číselné vyjádření v bytech
   * @param string|int $sSize
   * @return int
   */
  public static function convertPHPSizeToBytes($sSize){
    if (is_numeric($sSize)){
      return $sSize;
    }
    $sSuffix = substr($sSize, -1);
    $iValue = substr($sSize, 0, -1);
    switch(strtoupper($sSuffix)){
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'P':
        $iValue *= 1024;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'T':
        $iValue *= 1024;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'G':
        $iValue *= 1024;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'M':
        $iValue *= 1024;
      case 'K':
        $iValue *= 1024;
    }
    return $iValue;
  }
}