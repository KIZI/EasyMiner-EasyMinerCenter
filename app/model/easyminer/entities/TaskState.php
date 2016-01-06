<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;

use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class TaskState - pracovní třída pro zachycení stavu úlohy
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property string|null $state
 * @property int|null $rulesCount
 * @property string $importState
 * @property array|string $importData
 * @property string|null $resultsUrl
 */
class TaskState extends Object{
  /** @var null|string $state m:Enum(Task::STATE_*) */
  private $state;
  /** @var int|null $rulesCount */
  private $rulesCount;
  /** @var string|null $resultsUrl */
  private $resultsUrl;
  /** @var string $importState */
  private $importState;
  /** @var  array|string $importData */
  private $importData;

  /**
   * @param string|null $state = null
   * @param int|null $rulesCount = null
   * @param string $resultsUrl=null
   * @param string $importState
   * @param array|string $importData
   */
  public function __construct($state=null,$rulesCount=null,$resultsUrl=null,$importState=null,$importData=[]){
    $this->state=$state;
    $this->rulesCount=$rulesCount;
    $this->resultsUrl=$resultsUrl;
    $this->importState=$importState;
    $this->importData=$importData;
  }

  /**
   * @return null|string
   */
  public function getState(){
    return $this->state;
  }

  /**
   * @param $state
   */
  public function setState($state){
    $this->state=str_replace(' ','_',Strings::lower($state));
  }

  /**
   * @return string
   */
  public function getImportState() {
    return $this->importState;
  }

  /**
   * @param string $importState
   */
  public function setImportState($importState) {
    $this->importState=$importState;
  }

  /**
   * @return int|null
   */
  public function getRulesCount(){
    return $this->rulesCount;
  }

  /**
   * @param int|null $count
   */
  public function setRulesCount($count){
    $this->rulesCount=intval($count);
  }

  /**
   * @return string|null
   */
  public function getResultsUrl(){
    return $this->resultsUrl;
  }

  /**
   * @param string $url
   */
  public function setResultsUrl($url){
    if ($url!=''){
      $this->resultsUrl=$url;
    }else{
      $this->resultsUrl=null;
    }
  }

  /**
   * Funkce nastavující data o stavu importu
   * @param array $importData
   */
  public function setImportData(array $importData) {
    $this->importData=$importData;
  }

  /**
   * Funkce vracející data o stavu importu
   * @return array
   */
  public function getImportData() {
    return $this->importData;
  }

  /**
   * Funkce vracející info o stavu úlohy v podobě pole
   * @return array
   */
  public function asArray(){
    $result=array('state'=>$this->state);

    if ($this->rulesCount!==null){
      $result['rulesCount']=$this->rulesCount;
    }

    $result['resultsUrl']=$this->resultsUrl;

    $result['importState']=$this->importState;

    return $result;
  }
} 