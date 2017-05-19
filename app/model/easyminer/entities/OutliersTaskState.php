<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;

use Nette\Object;
use Nette\Utils\Strings;

/**
 * Class OutliersTaskState - working class representing a OutlierTask state
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property string|null $state
 * @property int|null $minerOutliersTaskId
 * @property string|null $resultsUrl
 */
class OutliersTaskState extends Object{
  /** @var  OutliersTask $outliersTask */
  private $outliersTask;
  /** @var null|string $state m:Enum(OutliersTask::STATE_*) */
  private $state;
  /** @var string|null $resultsUrl */
  private $resultsUrl;
  /** @var int $minerTaskId */
  private $minerOutliersTaskId;

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
   * @return int
   */
  public function getMinerOutliersTaskId(){
    return $this->minerOutliersTaskId;
  }

  /**
   * @param int $minerOutliersTaskId
   */
  public function setMinerOutliersTaskId($minerOutliersTaskId){
    $this->minerOutliersTaskId=$minerOutliersTaskId;
  }

  /**
   * Method returning info about the task state in form of an array
   * @return array
   */
  public function asArray(){
    $result=[
      'outliersTaskId'=>@$this->outliersTask->outliersTaskId,
      'state'=>$this->state
    ];

    $result['resultsUrl']=$this->resultsUrl;
    $result['minerOutliersTaskId']=$this->minerOutliersTaskId;

    return $result;
  }
} 