<?php

namespace App\Model\Data\Files;

/**
 * Class CsvImport - model pro import CSV souborů
 * @package App\Model\Data\Files
 */
class CsvImport {

  /**
   * Funkce pro změnu kódování souboru (ze zadaného kódování na UTF8)
   * @param string $filename
   * @param string $originalEncoding
   */
  public static function iconvFile($filename,$originalEncoding){
    $originalFilePath=$filename.'.original';
    if (!file_exists($originalFilePath)){
      rename($filename,$originalFilePath);
    }

    if ($originalEncoding=='utf8'){
      copy($originalFilePath,$filename);
      return;
    }

    $file=fopen($originalFilePath,'r');
    $file2=fopen($filename,'w');
    while ($row=fgets($file)){
      $rowNew=iconv($originalEncoding,'utf-8',$row);
      fputs($file2,$rowNew);
    }
    fclose($file2);
    fclose($file);
  }

  /**
   * Funkce vracející zvolený počet řádků z CSV souboru (ignoruje 1. řádek se záhlavím)
   * @param $filename
   * @param int $count = 10000
   * @param string $delimitier = ','
   * @param string $enclosure = '"'
   * @param string $escapeCharacter = '\\'
   * @return array
   */
  public static function getRowsFromCSV($filename,$count=10000,$delimitier=',',$enclosure='"',$escapeCharacter='\\'){
    $file=fopen($filename,'r');
    if ($file===false){return null;}
    $counter=$count;
    $outputArr=array();
    if ($delimitier=='\t'){
      $delimitier="\t";
    }
    while (($counter>0)&&($data=fgetcsv($file,0,$delimitier,$enclosure,$escapeCharacter))){
    ///while (($counter>0)&&($data=fgetcsv($file,0,$delimitier,$enclosure))){ //pro starší verze PHP
      if ($counter==$count){
        $counter--;
        continue;
      }
      $outputArr[]=$data;
      $counter--;
    }
    fclose($file);
    return $outputArr;
  }

  /**
   * Funkce vracející počet řádků v CSV souboru
   * @param string $filename
   * @return int
   */
  public static function getRowsCountInCsv($filename){
    $file=fopen($filename,'r');
    $rowsCount=0;
    while (fgets($file)){
      $rowsCount++;
    }
    fclose($file);
    return $rowsCount-1;
  }

  /**
   * Funkce vracející oddělovač, který je pravděpodobně použit v CSV souboru
   * @param string $filename
   * @return string
   */
  public static function getCSVDelimitier($filename){
    $file=fopen($filename,'r');
    if ($file===false){return ',';}
    if ($row=fgets($file)){
      $stredniky=substr_count($row,';');
      $carky=substr_count($row,',');
      $svislitka=substr_count($row,'|');
      $max=max($stredniky,$carky,$svislitka);
      if ($max<2){return ',';}
      switch ($max) {
        case $stredniky:return ';';
        case $carky:return ',';
        case $svislitka:return '|';
      }
    }
    fclose($file);
    return ',';
  }
} 