<?php

namespace App\Model\Data\Files;

/**
 * Class ZipImport - třída pro práci se ZIP archívy
 * @package App\Model\Data\Files
 */
class ZipImport {

  /**
   * Funkce vracející seznam souborů v archívu
   * @param $zipFileName
   * @return array
   */
  public static function getFilesList($zipFileName){
    $filesList=[];
    $zipArchive=self::openZipFile($zipFileName);
    for ($i = 0; $i < $zipArchive->numFiles; $i++) {
      $filename = $zipArchive->getNameIndex($i);
      $filesList[$i]=$filename;
    }
    @$zipArchive->close();
    return $filesList;
  }

  /**
   * Funkce pro dekódování vybraného souboru ze ZIP archívu
   * @param string $zipFileName
   * @param int $fileIndex
   * @param string $finalFileName
   * @return bool
   */
  public static function unzipFile($zipFileName, $fileIndex, $finalFileName){
    $zipArchive=self::openZipFile($zipFileName);
    $filename = $zipArchive->getNameIndex($fileIndex);
    copy("zip://".$zipFileName."#".$filename, $finalFileName);
    @$zipArchive->close();
    return file_exists($finalFileName);
  }

  /**
   * Funkce pro dekódování vybraného souboru ze ZIP archívu
   * @param string $zipFileName
   * @param string $compressedFileName
   * @param string $finalFileName
   */
  public static function unzipFileByName($zipFileName, $compressedFileName, $finalFileName){
    $filesList=self::getFilesList($zipFileName);
    foreach($filesList as $index=>$fileName){
      if ($compressedFileName==$fileName){
        self::unzipFile($zipFileName, $index, $finalFileName);
      }
    }
  }


  /**
   * @param string $zipFileName
   * @return \ZipArchive
   */
  protected static function openZipFile($zipFileName){
    $zipArchive=new \ZipArchive();
    if (!$zipArchive->open($zipFileName)){throw new \InvalidArgumentException('Archive cannot be opened.');}
    return $zipArchive;
  }

}