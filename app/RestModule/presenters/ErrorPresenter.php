<?php

namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Application\UI\ResourcePresenter;

/**
 * Class ErrorPresenter - returns error in form of XML or JSON
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ErrorPresenter extends ResourcePresenter {

  /**
   * Provide error to client
   * @param \Exception $exception
   */
  public function actionDefault($exception) {
    $this->sendErrorResource($exception);
  }

}