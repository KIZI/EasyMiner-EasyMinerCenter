<?php

namespace EasyMinerCenter\Model\Preprocessing\Entities;

/**
 * Class PpTask
 *
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 *
 * @property string $taskId
 * @property string $taskName
 * @property string $statusMessage
 * @property string $statusLocation
 */
class PpTask {

  public $taskId = '';
  public $taskName = '';
  public $statusMessage = '';
  public $statusLocation=null;
  public $resultLocation=null;

  /**
   * @param string $taskId = ''
   * @param string $taskName = ''
   * @param string $statusMessage = ''
   * @param string|null $statusLocation = null
   * @param string|null $resultLocation = null
   */
  public function __construct($taskId='', $taskName='', $statusMessage='', $statusLocation=null, $resultLocation=null){
    $this->taskId=$taskId;
    $this->taskName=$taskName;
    $this->statusMessage=$statusMessage;
    $this->statusLocation=$statusLocation;
    $this->resultLocation=$resultLocation;
  }
}