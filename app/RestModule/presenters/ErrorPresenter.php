<?php

namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Application\UI\ResourcePresenter;

/**
 * Base API ErrorPresenter - returns error in form of XML or JSON
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
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