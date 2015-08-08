<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;

use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class TaskState - pracovní třída pro zachycení stavu úlohy
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property string|null $state
 * @property int|null $rulesCount
 * @property string|null $resultsUrl
 */
class TaskState extends Object{
  /** @var null|string $state m:Enum(Task::STATE_*) */
  private $state;
  /** @var int|null $rulesCount */
  private $rulesCount;
  /** @var string|null $resultsUrl */
  private $resultsUrl;

  /**
   * @param string|null $state = null
   * @param int|null $rulesCount = null
   */
  public function __construct($state=null,$rulesCount=null,$resultsUrl=null){
    $this->state=$state;
    $this->rulesCount=$rulesCount;
    $this->resultsUrl=$resultsUrl;
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
   * Funkce vracející info o stavu úlohy v podobě pole
   * @return array
   */
  public function asArray(){
    $result=array('state'=>$this->state);

    if ($this->rulesCount!==null){
      $result['rulesCount']=$this->rulesCount;
    }

    $result['resultsUrl']=$this->resultsUrl;

    return $result;
  }
} 