<?php

namespace EasyMinerCenter\Model\Data\Files;

/**
 * Class ZipImport - class for work with ZIP archives
 * @package EasyMinerCenter\Model\Data\Files
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ZipImport {

  /**
   * Static method returning list of files in ZIP archive
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
   * Static method for unzipping a file from ZIP archive - identified by file index
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
   * Static method for unzipping of a file from ZIP archive - identified by filename
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