<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;

use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class OutliersTaskState - pracovní třída pro zachycení stavu úlohy
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @property string|null $state
 * @property string|null $resultsUrl
 */
class OutliersTaskState extends Object{
  /** @var  OutliersTask $outliersTask */
  private $outliersTask;
  /** @var null|string $state m:Enum(OutliersTask::STATE_*) */
  private $state;
  /** @var string|null $resultsUrl */
  private $resultsUrl;

  /**
   * TaskState constructor.
   * @param OutliersTask $outliersTask
   * @param string|null $state
   */
  public function __construct(OutliersTask $outliersTask,$state=null){
    $this->outliersTask=$outliersTask;
    $this->state=$state;
  }

  /**
   * @return OutliersTask
   */
  public function getOutliersTask(){
    return $this->outliersTask;
  }

  /**
   * @param OutliersTask $outliersTask
   */
  public function setOutliersTask(OutliersTask $outliersTask){
    $this->outliersTask=$outliersTask;
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
    $result=[
      'outliersTaskId'=>@$this->outliersTask->outliersTaskId,
      'state'=>$this->state
    ];

    $result['resultsUrl']=$this->resultsUrl;

    return $result;
  }
} 