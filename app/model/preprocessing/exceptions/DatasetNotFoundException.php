<?php

namespace EasyMinerCenter\Model\Preprocessing\Exceptions;

/**
 * Class DatasetNotFoundException
 * @package EasyMinerCenter\Model\Preprocessing\Entities
 * @author Stanislav Vojíř
 */
class DatasetNotFoundException extends PreprocessingException{

  /**
   * @param string $message = 'Requested dataset was not found!'
   * @param int $code = 404
   * @param \Exception|null $previous = null
   */
  public function __construct($message = 'Requested dataset was not found!', $code = 404, \Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }

}