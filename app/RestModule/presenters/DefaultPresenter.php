<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;

/**
 * Class DefaultPresenter - UI presenter pro zprovoznění swagger přístupu k API
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 */
class DefaultPresenter extends Presenter{

  /**
   * Akce pro přesměrování na Swagger UI
   */
  public function actionDefault(){
    $this->forward('Swagger:ui');
  }

}