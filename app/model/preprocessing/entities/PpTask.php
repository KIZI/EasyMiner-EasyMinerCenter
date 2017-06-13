<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpTask
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property string $taskId
 * @property string $taskName
 * @property string|null $statusMessage
 * @property string|null $statusLocation
 */
class PpTask {

  public $taskId = '';
  public $taskName = '';
  public $statusMessage = '';
  public $statusLocation=null;
  public $resultLocation=null;

  /**
   * @param string|array $taskId = ''
   * @param string $taskName = ''
   * @param string $statusMessage = ''
   * @param string|null $statusLocation = null
   * @param string|null $resultLocation = null
   */
  public function __construct($taskId='', $taskName='', $statusMessage='', $statusLocation=null, $resultLocation=null){
    if (is_array($taskId)){
      $this->prepareInstance($taskId);
    }else{
      $this->taskId=$taskId;
      $this->taskName=$taskName;
      $this->statusMessage=$statusMessage;
      $this->statusLocation=$statusLocation;
      $this->resultLocation=$resultLocation;
    }
  }

  /**
   * Private method for initialization of a PpTask using the given params
   * @param array $paramsArr
   */
  private function prepareInstance($paramsArr) {
    $this->statusMessage=@$paramsArr['statusMessage'];
    $this->statusLocation=@$paramsArr['statusLocation'];
    $this->resultLocation=@$paramsArr['resultLocation'];
    $this->taskId=@$paramsArr['taskId'];
    $this->taskName=@$paramsArr['taskName'];
  }

  /**
   * Method returning the URL for next request
   * @return null|string
   */
  public function getNextLocation() {
    return $this->statusLocation!=''?$this->statusLocation:$this->resultLocation;
  }
}