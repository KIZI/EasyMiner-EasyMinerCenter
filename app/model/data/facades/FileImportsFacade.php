<?php
namespace App\Model\Data\Facades;
use App\Model\Data\Files\CsvImport;

/**
 * Class FileImportsFacade - model pro práci s importy souborů
 * @package App\Model\Data\Facades
 */
class FileImportsFacade {
  /** @var  string $dataDirectory */
  private $dataDirectory;

  public function __construct($dataDirectory){
    $this->dataDirectory=$dataDirectory;
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
   * Funkce vracející data z CSV souboru
   * @param string $filename
   * @param int $count
   * @param string $delimitier
   * @param string $enclosure
   * @param string $escapeCharacter
   * @return array
   */
  public function getRowsFromCSV($filename,$count=10000,$delimitier=',',$enclosure='"',$escapeCharacter='\\'){
    return CsvImport::getRowsFromCSV($this->getFilePath($filename),$count,$delimitier,$enclosure,$escapeCharacter);
  }

  /**
   * Funkce vracející počet řádků
   * @param string $filename
   * @return int
   */
  public function getRowsCountInCSV($filename){
    return CsvImport::getRowsCountInCsv($filename);
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



} 