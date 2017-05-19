<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;

/**
 * Class DefaultPresenter - dummy, default presenter for API
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DefaultPresenter extends Presenter{

  /**
   * Action for default redirect to Swagger UI
   */
  public function actionDefault(){
    $this->forward('Swagger:ui');
  }

}