<?php

namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Application\UI\ResourcePresenter;

/**
 * Base API ErrorPresenter - returns error in form of XML or JSON
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