<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use Nette;
use Tracy\Debugger;

/**
 * Class ErrorPresenter
 * @package EasyMinerCenter\InstallModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ErrorPresenter extends Nette\Application\UI\Presenter {
  /**
   * @param  Exception
   * @return void
   * @throws Nette\Application\AbortException
   */
  public function actionDefault($exception) {
    if ($exception instanceof Nette\Application\BadRequestException) {
      $code = $exception->getCode();
      // load template 403.latte or 404.latte or ... 4xx.latte
      $this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx');
      if (Debugger::isEnabled()){
        // log to access.log
        Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
      }
    } else {
      $this->setView('500'); // load template 500.latte
      if (Debugger::isEnabled()) {
        Debugger::log($exception, Debugger::ERROR); // and log exception
      }
    }
    $this->template->exception = $exception;

    if ($this->isAjax()) { // AJAX request? Note this error in payload.
      $this->payload->error = true;
      $this->terminate();
    }
  }

}
