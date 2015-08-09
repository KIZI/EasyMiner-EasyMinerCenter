<?php

namespace EasyMinerCenter\InstallModule\Model;

/**
 * Class FilesManager - tøída spravující soubory a složky pøi instalaci
 * @package EasyMinerCenter\InstallModule\Model
 */
class FilesManager {
  /** @var  array $config */
  private $config;

  public static function getRootDirectory() {
    return APP_ROOT.'/..';
  }

  /**
   * Funkce pro kontrolu zapisovatelnosti souboru - pokud neexistuje, pokusí se jej vytvoøit
   * @param string $filename
   * @return bool
   */
  public static function checkWritableDirectory($filename){
    $filename=self::getRootDirectory().$filename;
    if (!is_dir($filename)){
      if (!@mkdir($filename,0777)){return false;}
    }
    if (!is_writable($filename)){
      if (!chmod($filename,0777)){return false;}
    }
    return true;
  }

  /**
   * Funkce pro kontrolu zapisovatelnosti souboru - pokud neexistuje, pokusí se jej vytvoøit
   * @param string $filename
   * @return bool
   */
  public static function checkWritableFile($filename) {
    $filename=self::getRootDirectory().$filename;
    $file=@fopen($filename,'a+');
    if (!$file){return false;}
    fclose($file);
    return true;
  }

  /**
   * @param array $config
   */
  public function __construct($config){
    $this->config=$config;
  }

  public function checkWritableDirectories(){
    $resultArr=[];
    $directoriesArr=$this->config['writable']['directories'];
    if (!empty($directoriesArr)){
      foreach($directoriesArr as $directory){
        $resultArr[$directory]=self::checkWritableDirectory($directory);
      }
    }
    return $resultArr;
  }

  public function checkWritableFiles(){
    $resultArr=[];
    $filesArr=$this->config['writable']['files'];
    if (!empty($filesArr)){
      foreach($filesArr as $file){
        $resultArr[$file]=self::checkWritableFile($file);
      }
    }
    return $resultArr;
  }

}