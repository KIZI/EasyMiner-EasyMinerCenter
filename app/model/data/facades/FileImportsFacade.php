<?php
namespace App\Model\Data\Facades;
use App\Model\Data\Entities\DbConnection;
use App\Model\Data\Files\CsvImport;
use App\Model\Data\Files\ZipImport;
use Nette\Application\ApplicationException;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class FileImportsFacade - model pro práci s importy souborů
 * @package App\Model\Data\Facades
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
   * @param string $delimitier
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param int $offset = 0 - počet řádek, které se mají na začátku souboru přeskočit...
   * @return array
   */
  public function getRowsFromCSV($filename,$count=10000,$delimitier=',',$enclosure='"',$escapeCharacter='\\',$offset=0){
    return CsvImport::getRowsFromCSV($this->getFilePath($filename),$count,$delimitier,$enclosure,$escapeCharacter,$offset);
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
   * @param string $delimitier
   * @param string $enclosure
   * @param string $escapeCharacter
   * @return int
   */
  public function getColsCountInCSV($filename,$delimitier=',',$enclosure='"',$escapeCharacter='\\'){
    return CsvImport::getColsCountInCsv($this->getFilePath($filename),$delimitier,$enclosure,$escapeCharacter);
  }

  /**
   * Funkce vracející přehled sloupců v CSV souboru
   * @param string $filename
   * @param string $delimitier
   * @param string $enclosure
   * @param string $escapeCharacter
   * @return \App\Model\Data\Entities\DbColumn[]
   */
  public function getColsInCSV($filename,$delimitier=',',$enclosure='"',$escapeCharacter='\\'){
    return CsvImport::analyzeCSVColumns($this->getFilePath($filename),$delimitier,$enclosure,$escapeCharacter);
  }

  /**
   * Funkce vracející výchozí oddělovač...
   * @param string $filename
   * @return string
   */
  public function getCSVDelimitier($filename){
    return CsvImport::getCSVDelimitier($this->getFilePath($filename));
  }

  /**
   * Funkce vracející nové pracovní jméno souboru
   * @return string
   */
  public function getTempFilename(){
    $filename=time();
    while(file_exists($this->getFilePath($filename))){
      $filename.='x';
    }
    return $filename;
  }

  public function getFilePath($filename){
    return $this->dataDirectory.'/'.$filename;
  }

  /**
   * @param string $filename
   * @param DbConnection $dbConnection
   * @param string $table
   * @param string $encoding
   * @param string $delimitier
   * @param string $enclosure
   * @param string $escapeCharacter
   * @throws ApplicationException
   */
  public function importCsvFile($filename,DbConnection $dbConnection,&$table,$encoding='utf-8',$delimitier=',',$enclosure='"',$escapeCharacter='\\'){
    //připravení parametrů pro DB tabulku
    $this->changeFileEncoding($filename,$encoding);
    $csvColumns=$this->getColsInCSV($filename,$delimitier,$enclosure,$escapeCharacter);

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
      $result=$this->databasesFacade->importCsvFile($table,$colsNames,$this->getFilePath($filename),$delimitier,$enclosure,$escapeCharacter,1);
      if ($result){
        return;
      }else{
        $this->databasesFacade->truncateTable($table);
      }
      #endregion
    }

    #region postupný import pomocí samostatných dotazů
    $csvFile=CsvImport::openCsv($this->getFilePath($filename));
    CsvImport::getRowsFromOpenedCSVfile($csvFile,1,$delimitier,$enclosure,$escapeCharacter);//přeskakujeme první řádek

    while($row=CsvImport::getRowsFromOpenedCSVfile($csvFile,1,$delimitier,$enclosure,$escapeCharacter)){
      if (isset($row[0])){
        $row=$row[0];//chceme jen jeden řádek
      }
      $insertArr=array();
      for ($i=0;$i<$colsCount;$i++){
        if (isset($row[$i])){
          $insertArr[$colsNames[$i]]=($row[$i]!=''?$row[$i]:null);
        }else{
          $insertArr[$colsNames[$i]]=null;
        }

      }
      $this->databasesFacade->insertRow($table,$insertArr);
    }
    CsvImport::closeCsv($csvFile);
    #endregion
  }

} 