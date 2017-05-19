<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use EasyMinerCenter\Model\Data\Files\ZipImport;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use Nette\Application\ApplicationException;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class FileImportsFacade - facade for direct file import
 * @package EasyMinerCenter\Model\Data\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * xxx
 */
class FileImportsFacade {
  /** @var  string $dataDirectory */
  private $dataDirectory;
  /** @var bool $tryDirectFileImport */
  private $tryDirectFileImport=[];
  /** @var  DatabaseFactory $databaseFactory */
  private $databaseFactory;

  const FILE_TYPE_ZIP='Zip';
  const FILE_TYPE_CSV='Csv';
  const FILE_TYPE_UNKNOWN='';
  /** @const CSV_ANALYZE_ROWS - count of rows which should be analyzed for auto-detection of data types */
  const CSV_ANALYZE_ROWS=100;

  /**
   * FileImportsFacade constructor
   * @param string $dataDirectory
   * @param array $databases
   * @param DatabaseFactory $databaseFactory
   */
  public function __construct($dataDirectory, $databases, DatabaseFactory $databaseFactory){
    $this->dataDirectory=$dataDirectory;
    $this->databaseFactory=$databaseFactory;
    if (!empty($databases)){
      foreach ($databases as $dbType=>$params){
        if (@$params['allowFileImport']){
          $this->tryDirectFileImport[$dbType]=true;
        }
      }
    }
  }

  /**
   * Method for automatic decompression of ZIP archive
   * (if it is successfull, the file will be moved to the original place and the method returns final type of file)
   * @param string $fileName
   * @return string
   */
  public function tryAutoUnzipFile($fileName){
    $processableFilesList=$this->getZipArchiveProcessableFilesList($fileName);
    if (count($processableFilesList)==1){
      foreach($processableFilesList as $index=>$name){
        $this->uncompressFileFromZipArchive($fileName,$index);
        return $this->detectFileType($name);
      }
    }
    return self::FILE_TYPE_ZIP;
  }

  /**
   * Method for decompression of one file from ZIP (to the place of original ZIP archive)
   * @param string $fileName
   * @param int $selectedFileIndex
   * @throws \Exception
   */
  public function uncompressFileFromZipArchive($fileName,$selectedFileIndex){
    $fileName=$this->getFilePath($fileName);
    try{
      FileSystem::delete($fileName.'.unzipped');
    }catch (\Exception $e){/*ignore error*/}
    if (ZipImport::unzipFile($fileName,$selectedFileIndex,$fileName.'.unzipped')){
      FileSystem::rename($fileName.'.unzipped',$fileName,true);
    }else{
      throw new \Exception('Unzip failed.');
    }
  }

  /**
   * Method returning list of files in ZIP archive
   * @param string $fileName
   * @return array
   */
  public function getZipArchiveFilesList($fileName){
    return ZipImport::getFilesList($this->getFilePath($fileName));
  }

  /**
   * Method returning list of files from ZIP archive, which could be processed
   * @param string $fileName
   * @return array
   */
  public function getZipArchiveProcessableFilesList($fileName){
    $processableFilesList=[];
    $filesList=$this->getZipArchiveFilesList($fileName);
    if (!empty($filesList)){
      foreach ($filesList as $itemIndex=>$itemName){
        $itemFileType=$this->detectFileType($itemName);
        if ($itemFileType==self::FILE_TYPE_CSV){
          $processableFilesList[$itemIndex]=$itemName;
        }
      }
    }
    return $processableFilesList;
  }

  /**
   * Method for changing of file encoding
   * @param string $filename
   * @param string $originalEncoding
   */
  public function changeFileEncoding($filename,$originalEncoding){
    CsvImport::iconvFile($this->getFilePath($filename),$originalEncoding);
  }

  /**
   * Method for deleting of a working file (file for data import)
   * @param string $filename
   */
  public function deleteFile($filename){
    try{
      FileSystem::delete($this->getFilePath($filename));
    }catch (\Exception $e){}
    try{
      FileSystem::delete($this->getFilePath($filename.'.utf8'));
    }catch (\Exception $e){}
    try{
      FileSystem::delete($this->getFilePath($filename.'.cp1250'));
    }catch (\Exception $e){}
    try{
      FileSystem::delete($this->getFilePath($filename.'.iso-8859-1'));
    }catch (\Exception $e){}
  }

  /**
   * Method for deleting of old import files
   * @param int $minusDays
   */
  public function deleteOldFiles($minusDays){
    foreach (Finder::findFiles('*')->date('<', '- '.$minusDays.' days')->from($this->dataDirectory) as $file){
      FileSystem::delete($file);
    }
  }

  /**
   * Method for detecting of file type using its extension
   * @param string $filename
   * @return string self::FILE_TYPE_ZIP|self::FILE_TYPE_CSV
   */
  public function detectFileType($filename){
    $filenameInfo=pathinfo($filename);
    $filenameInfo=Strings::lower(@$filenameInfo['extension']);
    switch ($filenameInfo){
      case 'csv': return self::FILE_TYPE_CSV;
      case 'zip': return self::FILE_TYPE_ZIP;
    }
    return self::FILE_TYPE_UNKNOWN;
  }


  /**
   * Method returning rows from CSV file
   * @param string $filename
   * @param int $count
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param int $offset = 0 - count of rows, which should be skipped
   * @param string|null $nullValue = null
   * @return array
   */
  public function getRowsFromCSV($filename,$count=10000,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=null,$offset=0){
    return CsvImport::getRowsFromCSV($this->getFilePath($filename),$count,$delimiter,$enclosure,$escapeCharacter,$nullValue,$offset);
  }

  /**
   * Method returning count of rows in CSV file
   * @param string $filename
   * @return int
   */
  public function getRowsCountInCSV($filename){
    return CsvImport::getRowsCountInCsv($this->getFilePath($filename));
  }

  /**
   * Method returning count of columns in CSV file
   * @param string $filename
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @return int
   */
  public function getColsCountInCSV($filename,$delimiter=',',$enclosure='"',$escapeCharacter='\\'){
    return CsvImport::getColsCountInCsv($this->getFilePath($filename),$delimiter,$enclosure,$escapeCharacter);
  }

  /**
   * Method returning list of columns in CSV file
   * @param string $filename
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param int $analyzeRowsCount = max(self::CSV_ANALYZE_ROWS,given value)
   * @param bool $requireSafeColumnNames=true - if true, the column names will be sanitized to be usable in all database types
   * @param string $nullValue="" - value which should be interpreted ar null
   * @return DbField[]
   */
  public function getColsInCSV($filename,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$analyzeRowsCount=self::CSV_ANALYZE_ROWS, $requireSafeColumnNames=true, $nullValue=""){
    return CsvImport::analyzeCSVColumns($this->getFilePath($filename),$delimiter,$enclosure,$escapeCharacter,max($analyzeRowsCount,self::CSV_ANALYZE_ROWS), $requireSafeColumnNames, $nullValue);
  }

  /**
   * Funkce vracející výchozí oddělovač...
   * Method returning the default columns delimiter
   * @param string $filename
   * @return string
   */
  public function getCSVDelimiter($filename){
    return CsvImport::getCSVDelimiter($this->getFilePath($filename));
  }

  /**
   * Method returning new working filename
   * @param  string $extension
   * @return string
   */
  public function getTempFilename($extension=''){
    $filename=time();
    while(file_exists($this->getFilePath($filename))){
      $filename.='x';
    }
    return $filename.($extension?'.'.$extension:'');
  }

  public function getFilePath($filename){
    return $this->dataDirectory.'/'.$filename;
  }

  /**
   * Method for check, if there exists the selected file for import
   * @param string $filename
   * @return bool
   */
  public function checkFileExists($filename) {
    return file_exists($this->dataDirectory.'/'.$filename);
  }

  /**
   * Method for saving temporal file content, in case o archive, it will be unpacked
   * @param $fileContent
   * @param string $compression
   * @return string
   */
  public function saveTempFileWithDecompression($fileContent, $compression=''){
    $compression=strtolower($compression);

    switch($compression){
      case 'zip':
        $filename=$this->saveTempFile($fileContent,'zip');
        return $this->unzipFile($filename);
      default:
        return $this->saveTempFile($fileContent);
    }
  }

  /**
   * Method for decompression of first file from archive
   * @param string $filename
   * @return string
   */
  private function unzipFile($filename){
    $zip=new \ZipArchive();
    $filepath=$this->getFilePath($filename);

    if ($zip->open($filepath)){
      $compressedFilename = $zip->getNameIndex(0);
      $filename2=$this->getTempFilename();
      copy('zip://'.$filepath.'#'.$compressedFilename,$this->getFilePath($filename2));
      $zip->close();
      unlink($filepath);
      return $filename2;
    }else{
      return $filename;
    }
  }

  /**
   * Method for saving a temp file
   * @param string $fileContent
   * @param string $extension
   * @return string
   */
  public function saveTempFile($fileContent,$extension=''){
    $filename=$this->getTempFilename($extension);
    file_put_contents($this->getFilePath($filename),$fileContent);
    return $filename;
  }

  /**
   * Method for import of a CSV file
   * @param string $filename
   * @param string $dbType
   * @param User $user
   * @param string $tableName
   * @param string $encoding='utf-8'
   * @param string $delimiter=','
   * @param string $enclosure='"'
   * @param string $escapeCharacter='\\'
   * @param string $nullValue=''
   * @return DbDatasource
   * @throws \Exception
   */
  public function importCsvFile($filename,$dbType,User $user,$tableName,$encoding='utf-8',$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=''){
    $columns=$this->getColsInCSV($filename,$delimiter,$enclosure,$escapeCharacter);
    if (empty($columns)){
      throw new \Exception('No columns detected!');
    }

    $dataTypes=[];
    foreach($columns as $column) {
      $dataTypes[]=($column->type==DbField::TYPE_NOMINAL?'nominal':'numeric');
    }

    $database=$this->databaseFactory->getDatabaseInstanceWithDefaultDbConnection($dbType, $user);
    return $database->importCsvFile($this->getFilePath($filename),$tableName,$encoding,$delimiter,$enclosure,$escapeCharacter,$nullValue,$dataTypes);
  }

  /**
   * Method returning max size of file, which can be uploaded
   * @return int
   */
  public function getMaximumFileUploadSize() {
    return min(self::convertPHPSizeToBytes(ini_get('post_max_size')), self::convertPHPSizeToBytes(ini_get('upload_max_filesize')));
  }

  /**
   * Method for conversion of file sizes from PHP value to size in bytes
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

  /**
   * @param string $filename
   * @return string
   */
  public static function sanitizeFileNameForImport($filename){
    $filenameArr=pathinfo($filename);
    $result=trim(Strings::webalize($filenameArr['filename']));
    return str_replace(['.','-'], ['_','_'], $result);
  }

} 