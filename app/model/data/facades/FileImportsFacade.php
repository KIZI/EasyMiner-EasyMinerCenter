<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use EasyMinerCenter\Model\Data\Files\ZipImport;
use Nette\Application\ApplicationException;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class FileImportsFacade - model pro práci s importy souborů
 * @package EasyMinerCenter\Model\Data\Facades
 */
class FileImportsFacade {
  /** @var  string $dataDirectory */
  private $dataDirectory;
  /** @var DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var bool $tryDirectFileImport */
  private $tryDirectFileImport=[];

  const FILE_TYPE_ZIP='Zip';
  const FILE_TYPE_CSV='Csv';
  const FILE_TYPE_UNKNOWN='';
  /*počet řádků, které mají být analyzovány pro určení datových typů v CSV souboru*/
  const CSV_ANALYZE_ROWS=100;

  public function __construct($dataDirectory, $databases, DatabasesFacade $databasesFacade){
    $this->dataDirectory=$dataDirectory;
    $this->databasesFacade=$databasesFacade;
    if (!empty($databases)){
      foreach ($databases as $dbType=>$params){
        if (@$params['allowFileImport']){
          $this->tryDirectFileImport[$dbType]=true;
        }
      }
    }
  }

  /**
   * Funkce pro automatické dekódování ZIP archívu (pokud projde, soubor přesune na místo původního souboru a následně vrátí finální typ souboru
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
   * Funkce pro extrakci konkrétního souboru ze ZIP archívu (na místo původního souboru)
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
   * Funkce vracející seznam souborů v ZIP archívu
   * @param string $fileName
   * @return array
   */
  public function getZipArchiveFilesList($fileName){
    return ZipImport::getFilesList($this->getFilePath($fileName));
  }

  /**
   * Funkce vracející seznam souborů ze ZIP archívu, které je možné dál zpracovat
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
   * Funkce pro změnu kódování datového souboru
   * @param string $filename
   * @param string $originalEncoding
   */
  public function changeFileEncoding($filename,$originalEncoding){
    CsvImport::iconvFile($this->getFilePath($filename),$originalEncoding);
  }

  /**
   * Funkce pro smazání pracovních souborů sloužících k importům dat
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
   * Funkce pro smazání starých souborů importů (starších, než daný počet dnů)
   * @param int $minusDays
   */
  public function deleteOldFiles($minusDays){
    foreach (Finder::findFiles('*')->date('<', '- '.$minusDays.' days')->from($this->dataDirectory) as $file){
      FileSystem::delete($file);
    }
  }

  /**
   * Funkce detekující typ souboru podle jeho přípony
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
   * Funkce vracející data z CSV souboru
   * @param string $filename
   * @param int $count
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param int $offset = 0 - počet řádek, které se mají na začátku souboru přeskočit...
   * @param string|null $nullValue = null
   * @return array
   */
  public function getRowsFromCSV($filename,$count=10000,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=null,$offset=0){
    return CsvImport::getRowsFromCSV($this->getFilePath($filename),$count,$delimiter,$enclosure,$escapeCharacter,$nullValue,$offset);
  }

  /**
   * Funkce vracející počet řádků
   * @param string $filename
   * @return int
   */
  public function getRowsCountInCSV($filename){
    return CsvImport::getRowsCountInCsv($this->getFilePath($filename));
  }

  /**
   * Funkce vracející počet sloupců v CSV souboru
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
   * Funkce vracející přehled sloupců v CSV souboru
   * @param string $filename
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param int $analyzeRowsCount = max(self::CSV_ANALYZE_ROWS,given value)
   * @return DbField[]
   */
  public function getColsInCSV($filename,$delimiter=',',$enclosure='"',$escapeCharacter='\\',$analyzeRowsCount=self::CSV_ANALYZE_ROWS){
    return CsvImport::analyzeCSVColumns($this->getFilePath($filename),$delimiter,$enclosure,$escapeCharacter,max($analyzeRowsCount,self::CSV_ANALYZE_ROWS));
  }

  /**
   * Funkce vracející výchozí oddělovač...
   * @param string $filename
   * @return string
   */
  public function getCSVDelimiter($filename){
    return CsvImport::getCSVDelimiter($this->getFilePath($filename));
  }

  /**
   * Funkce vracející nové pracovní jméno souboru
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
   * Funkce pro kontrolu, jestli existuje příslušný importovaný soubor
   * @param string $filename
   * @return bool
   */
  public function checkFileExists($filename) {
    return file_exists($this->dataDirectory.'/'.$filename);
  }

  /**
   * Funkce pro uložení obsahu dočasného souboru (s nově vygenerovaným dočasným názvem), v případě komprese dojde k jeho rozbalení
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
   * Funkce pro rozbalení 1. souboru z archívu
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
   * Funkce pro uložení obsahu dočasného souboru (s nově vygenerovaným dočasným názvem)
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
   * Funkce pro import CSV souboru
   * @param string $filename
   * @param DbConnection $dbConnection
   * @param string $table
   * @param string $encoding
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string|null $nullValue=null
   * @throws ApplicationException
   */
  public function importCsvFile($filename,DbConnection $dbConnection,&$table,$encoding='utf-8',$delimiter=',',$enclosure='"',$escapeCharacter='\\',$nullValue=""){
    //připravení parametrů pro DB tabulku
    $this->changeFileEncoding($filename,$encoding);
    $csvColumns=$this->getColsInCSV($filename,$delimiter,$enclosure,$escapeCharacter);

    //otevření databáze a vytvoření DB tabulky
    $this->databasesFacade->openDatabase($dbConnection);
    if (!$this->databasesFacade->createTable($table,$csvColumns)){
      throw new ApplicationException('Table creation failed!');
    }

    //projití CSV souboru a import dat do DB
    $colsNames=array();
    foreach ($csvColumns as $column){
      $colsNames[]=$column->name;
    }
    $colsCount=count($colsNames);

    if (@$this->tryDirectFileImport[$dbConnection->type]){
      #region try direct import...
      $result=$this->databasesFacade->importCsvFile($table,$colsNames,$this->getFilePath($filename),$delimiter,$enclosure,$escapeCharacter,$nullValue,1,DatabasesFacade::FIRST_DB);
      if ($result){
        return;
      }else{
        $this->databasesFacade->truncateTable($table);
      }
      #endregion
    }

    #region postupný import pomocí samostatných dotazů
    $csvFile=CsvImport::openCsv($this->getFilePath($filename));
    CsvImport::getRowsFromOpenedCSVFile($csvFile,1,$delimiter,$enclosure,$escapeCharacter,$nullValue);//přeskakujeme první řádek

    while($row=CsvImport::getRowsFromOpenedCSVFile($csvFile,1,$delimiter,$enclosure,$escapeCharacter,$nullValue)){
      if (isset($row[0])){
        $row=$row[0];//chceme jen jeden řádek
      }
      $insertArr=array();
      for ($i=0;$i<$colsCount;$i++){
        if (isset($row[$i])){
          $insertArr[$colsNames[$i]]=$row[$i];
        }else{
          $insertArr[$colsNames[$i]]=null;
        }

      }
      $this->databasesFacade->insertRow($table,$insertArr);
    }
    CsvImport::closeCsv($csvFile);
    #endregion
  }

  /**
   * Funkce vracející maximální velikost souboru, který lze uploadovat
   * @return int
   */
  public function getMaximumFileUploadSize() {
    return min(self::convertPHPSizeToBytes(ini_get('post_max_size')), self::convertPHPSizeToBytes(ini_get('upload_max_filesize')));
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