<?php

namespace EasyMinerCenter\InstallModule\Model;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

/**
 * Class FilesManager - třída spravující soubory a složky při instalaci
 * @package EasyMinerCenter\InstallModule\Model
 */
class FilesManager {
  /** @var  array $config */
  private $config;

  public static function getRootDirectory() {
    return APP_ROOT.'/..';
  }

  /**
   * Funkce pro kontrolu zapisovatelnosti souboru - pokud neexistuje, pokusí se jej vytvořit
   * @param string $filename
   * @return bool
   */
  public static function checkWritableDirectory($filename){
    $filename=self::getRootDirectory().$filename;
    if (!is_dir($filename)){
      if (!@mkdir($filename,0777)){return false;}
    }
    if (!is_writable($filename)){
      if (!@chmod($filename,0777)){return false;}
    }
    return true;
  }

  /**
   * Funkce pro kontrolu zapisovatelnosti souboru - pokud neexistuje, pokusí se jej vytvořit
   * @param string $filename
   * @param bool $chmod = true
   * @return bool
   */
  public static function checkWritableFile($filename,$chmod=true) {
    $filenamePath=self::getRootDirectory().$filename;
    $file=@fopen($filenamePath,'a+');
    if (!$file){
      if (file_exists($filenamePath) && $chmod){
        @chmod($filenamePath,0666);
        return self::checkWritableFile($filename);
      }
      return false;
    }
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

  /**
   * @param string $section = [writable]
   * @param string $type = [files|directories]
   * @param string $id
   * @param bool $absolutePath=false
   * @return string
   */
  public function getPath($section, $type, $id, $absolutePath=false) {
    $path=$this->config[$section][$type][$id];
    if (!empty($path) && $absolutePath){
      return self::getRootDirectory().$path;
    }else{
      return $path;
    }
  }

  /**
   * Funkce pro provedení závěrečných operací
   */
  public function finallyOperations() {
    #region chmod
    if (!empty($this->config['finally']['chmod'])){
      $chmodOperations=$this->config['finally']['chmod'];
      foreach($chmodOperations as $userRights=>$filesArr){
        if (!empty($filesArr)){
          foreach($filesArr as $file){
            try{
              $filePath=self::getRootDirectory().$file;
              @chmod($filePath,'0'.$userRights);
            }catch (\Exception $e){/*chmod chybu ignorujeme...*/}
          }
        }
      }
    }
    #endregion chmod
    #region delete
    if (!empty($this->config['finally']['delete'])){
      foreach ($this->config['finally']['delete'] as $file){
        try{
          FileSystem::delete(self::getRootDirectory().$file);
        }catch (\Exception $e){/*ignore error*/}
      }
    }
    #endregion delete
    #region clear directories
    if (!empty($this->config['finally']['clearDirectories'])){
      foreach ($this->config['finally']['clearDirectories'] as $directory){
        try{
          $finderItems=Finder::find('*')->from(self::getRootDirectory().$directory);
          if ($finderItems->count()>0){
            foreach($finderItems as $item){
              try{
                /** @noinspection PhpUndefinedMethodInspection */
                FileSystem::delete($item->getPathName());
              }catch (\Exception $e){/*ignorujeme chybu*/}
            }
          }
        }catch (\Exception $e){/*ignore error*/}
      }
    }
    #endregion clear directories
  }

  /**
   * Funkce vracející pole souborů, které by měly být nastavené do režimu jen pro čtení s výsledkem jejich kontroly
   * @return array
   */
  public function checkFinallyReadonlyFiles() {
    $files=$this->config['finally']['chmod']['444'];
    $resultArr=[];
    if (!empty($files)){
      foreach ($files as $file){
        $state=!self::checkWritableFile($file,false);
        if (!$state){
          @chmod(self::getRootDirectory().$file,0444);
          $state=!self::checkWritableFile($file,false);
        }
        $resultArr[$file]=$state;
      }
    }
    return $resultArr;
  }
}