<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Utils\Strings;
use Tracy\Debugger;

/**
 * Class DefaultPresenter - UI presenter pro zprovoznění swagger přístupu k API
 * @package EasyMinerCenter\RestModule\Presenters
 */
class DefaultPresenter extends Presenter{

  /**
   * Akce pro přesměrování na Swagger UI
   */
  public function actionDefault() {
    $this->forward('Swagger:ui');
  }

}