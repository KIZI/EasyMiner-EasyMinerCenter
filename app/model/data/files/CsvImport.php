<?php

namespace EasyMinerCenter\Model\Data\Files;
use EasyMinerCenter\Model\Data\Entities\DbField;
use Nette\Utils\Strings;

/**
 * Class CsvImport - class for import of CSV files
 * @package EasyMinerCenter\Model\Data\Files
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CsvImport {

  /**
   * Method returning array with list of possible null values
   * @return array
   */
  public static function getDefaultNullValuesArr() {
    return [
      ''=>'Empty string',
      'NaN'=>'NaN',
      'NULL'=>'NULL',
      'N/A'=>'N/A',
      'null'=>'null',
      '-'=>'-',
      '#REF!'=>'#REF!',
      '#VALUE!'=>'#VALUE!',
      '?'=>'?',
      '#NULL!'=>'#NULL!',
      '#NUM!'=>'#NUM!',
      '#DIV/0'=>'#DIV/0',
      'n/a'=>'n/a',
      '#NAME?'=>'#NAME?',
      'NIL'=>'NIL',
      'nil'=>'nil',
      'na'=>'na',
      '#N/A'=>'#N/A',
      'NA'=>'NA',
      'none'=>'none'
    ];
  }

  /**
   * Method for changing the file encoding (from the given encoding to UTF8)
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
   * Method returning rows from CSV file
   * @param $filename
   * @param int $count = 10000
   * @param string $delimiter = ','
   * @param string $enclosure = '"'
   * @param string $escapeCharacter = '\\'
   * @param string|null $nullValue = null
   * @param int $offset = 0
   * @return array
   */
  public static function getRowsFromCSV($filename,$count=10000,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=null,$offset=0){
    $file=fopen($filename,'r');
    if ($file===false){return null;}
    $counter=0;
    $outputArr=array();
    if ($delimiter=='\t'){
      $delimiter="\t";
    }
    while($offset>0){
      //skip rows
      fgets($file);
      $offset--;
    }

    while (($counter<$count)&&($data=fgetcsv($file,null,$delimiter,$enclosure,$escapeCharacter))){
      if ($nullValue!==null){
        //ignore null values...
        foreach ($data as &$value) {
          if($value==$nullValue) {
            $value='';
          }
        }
      }
      $outputArr[]=$data;
      $counter++;
    }
    fclose($file);
    return $outputArr;
  }

  /**
   * Method returning column names from CSV file
   * @param string $filename
   * @param string $delimiter = ','
   * @param string $enclosure  = '"'
   * @param string $escapeCharacter = '\\'
   * @return string[]
   */
  public static function getColsNamesInCsv($filename,$delimiter=',',$enclosure='"',$escapeCharacter='\\'){
    $columnNames=self::getRowsFromCSV($filename,1,$delimiter,$enclosure,$escapeCharacter,null,0)[0];

    for ($i=count($columnNames)-1;$i>=0;$i--){
      if (Strings::trim(Strings::fixEncoding($columnNames[$i]))==''){
        unset($columnNames[$i]);
      }
    }

    return $columnNames;
  }

  /**
   * Method returning count of columns in CSV file
   * @param string $filename
   * @param string $delimiter = ','
   * @param string $enclosure  = '"'
   * @param string $escapeCharacter = '\\'
   * @return int
   */
  public static function getColsCountInCsv($filename,$delimiter=',',$enclosure='"',$escapeCharacter='\\'){
    return count(self::getColsNamesInCsv($filename,$delimiter,$enclosure,$escapeCharacter));
  }

  /**
   * Method returning count of rows from CSV file
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
   * Method returning the probably used delimiter detected from CSV file
   * @param string $filename
   * @return string
   */
  public static function getCSVDelimiter($filename){
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

  /**
   * Method for sanitization of column names
   * @param string[] $columnNamesArr
   * @param bool $requireSafeColumnNames=true - if true, the column names should be sanitized for import into all databases
   * @param string $defaultColumnName='name'
   * @return string[]
   */
  public static function sanitizeColumnNames($columnNamesArr, $requireSafeColumnNames=true,$defaultColumnName='name'){
    $finalColumnNamesArr=[];
    $finalColumnNamesFilterArr=['id'];
    $i=0;
    foreach($columnNamesArr as $name){
      $i++;
      //removing of empty characters from start and end of the column name
      $name=Strings::trim($name);
      //check for possible empty column name
      if ($name==''){
        $name=$defaultColumnName.$i;
      }
      if ($requireSafeColumnNames){
        //solve the name
        $name=Strings::webalize($name,null,false);
        $name=str_replace('-','_',$name);
        $name=Strings::substring($name,0,25);
        if (!preg_match('/[a-z_]+\w*/i',$name)){
          $name='_'.$name;
        }
      }
      //solve possible duplicities
      $counter=0;
      do{
        if ($requireSafeColumnNames){
          $finalColumnName=$name.($counter>0?'_'.$counter:'');
        }else{
          $finalColumnName=$name.($counter>0?' - '.$counter:'');
        }
        $counter++;
      }while(in_array(Strings::lower($finalColumnName),$finalColumnNamesFilterArr));
      $finalColumnNamesArr[]=$finalColumnName;
      $finalColumnNamesFilterArr[]=Strings::lower($finalColumnName);
    }
    return $finalColumnNamesArr;
  }

  /**
   * Method returning info about CSV column names
   * @param string $filename
   * @param string $delimiter = ','
   * @param string $enclosure  = '"'
   * @param string $escapeCharacter = '\\'
   * @param int $analyzeRowsCount=100
   * @param bool $requireSafeColumnNames=true - if true, the column names should be sanitized for import into all databases
   * @param string $nullValue="" - values evaluated as null
   * @return DbField[]
   */
  public static function analyzeCSVColumns($filename, $delimiter=',',$enclosure='"',$escapeCharacter='\\',$analyzeRowsCount=100, $requireSafeColumnNames=true, $nullValue=""){
    $columnNamesArr=self::getColsNamesInCsv($filename,$delimiter,$enclosure,$escapeCharacter);

    //sanitization of columns names
    $columnNamesArr=self::sanitizeColumnNames($columnNamesArr, $requireSafeColumnNames);

    $numericalArr=[];
    $nonNullArr=[];
    $strlenArr=[];
    //default initialization of countins arrays
    $columnsCount=count($columnNamesArr);
    for($i=0;$i<$columnsCount;$i++){
      $numericalArr[$i]=1;
      $strlenArr[$i]=0;
      $nonNullArr[$i]=false;
    }

    $file=fopen($filename,'r');
    fgets($file);
    //check all rows in CSV file
    $rowsCount=0;
    while (($data=fgetcsv($file,0,$delimiter,$enclosure,$escapeCharacter))&&($rowsCount<$analyzeRowsCount)){
      //next row loaded
      for ($i=0;$i<$columnsCount;$i++){
        $value=@$data[$i];
        //check for null values
        if ($value==$nullValue){
          //skip the analyze of null values
          continue;
        }
        $nonNullArr[$i]=true;
        $isNumeric=self::checkIsNumeric($value);
        if ($numericalArr[$i]==1){
          $numericalArr[$i]=$isNumeric;
        }elseif($numericalArr[$i]==2){
          if ($isNumeric==0){
            $numericalArr[$i]=0;
          }
        }
        $strlen=strlen($value);
        if ($strlen>$strlenArr[$i]){
          $strlenArr[$i]=$strlen;
        }
      }
      $rowsCount++;
    }
    //collect info
    $outputArr=array();
    for ($i=0;$i<$columnsCount;$i++){
      if ($nonNullArr[$i]){
        if ($numericalArr[$i]==2||$numericalArr[$i]==1){
          $datatype=DbField::TYPE_NUMERIC;
        }else{
          $datatype=DbField::TYPE_NOMINAL;
        }
      }else{
        $datatype=DbField::TYPE_NOMINAL;
      }
      $outputArr[$i]=new DbField(null,null,$columnNamesArr[$i],$datatype,null);
    }
    fclose($file);
    return $outputArr;
  }

  /**
   * Method for checking, if the given value is number
   * @param string|int $value
   * @return int - 0=string, 1=int, 2=float
   */
  private static function checkIsNumeric($value){
    if ($value==''){
      return 1;
    }
    if (is_numeric($value)||(is_numeric(str_replace(',','.',$value)))){
      if (((string)intval(str_replace(',','.',$value)))==str_replace(',','.',$value)){
        return 1;
      }else{
        return 2;
      }
    }else{
      return 0;
    }
  }

  /**
   * @param string $filename
   * @return resource
   */
  public static function openCsv($filename){
    return fopen($filename,'r');
  }

  /**
   * @param resource $resource
   */
  public static function closeCsv($resource){
    fclose($resource);
  }

  /**
   * Method returning rows from CSV file (irgnoring 1. line with column names)
   * @param resource $fileResource
   * @param int $count = 10000
   * @param string $delimiter = ','
   * @param string $enclosure = '"'
   * @param string $escapeCharacter = '\\'
   * @param string|null $nullValue = null
   * @return array
   */
  public static function getRowsFromOpenedCSVFile($fileResource,$count=10000,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=null){
    $counter=0;
    $outputArr=array();
    if ($delimiter=='\t'){
      $delimiter="\t";
    }

    while (($counter<$count)&&($data=fgetcsv($fileResource,null,$delimiter,$enclosure,$escapeCharacter))){
      if ($nullValue!==null){
        foreach ($data as &$value){
          //filter null values
          if ($value==$nullValue){
            $value=null;
          }
        }
      }
      $outputArr[]=$data;
      $counter++;
    }
    return $outputArr;
  }
} 