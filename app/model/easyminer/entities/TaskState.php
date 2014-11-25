<?php
namespace App\Model\EasyMiner\Entities;

use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class TaskState - pracovní třída pro zachycení stavu úlohy
 * @package App\Model\EasyMiner\Entities
 * @property string|null $state
 * @property int|null $rulesCount
 */
class TaskState extends Object{
  /** @var null|string $state m:Enum(Task::STATE_*) */
  private $state;
  /** @var int|null $rulesCount */
  private $rulesCount;

  /**
   * @param string|null $state = null
   * @param int|null $rulesCount = null
   */
  public function __construct($state=null,$rulesCount=null){
    $this->state=$state;
    $this->rulesCount=$rulesCount;
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
   * @param $count
   */
  public function setRulesCount($count){
    $this->rulesCount=intval($count);
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
    return $result;
  }
} 