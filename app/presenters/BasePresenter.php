<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter{

  private $translator;

  function beforeRender(){

    $this->template->setTranslator($this->translator);

  }

  public function injectTranslator(Nette\Localization\ITranslator $translator){
    $this->translator=$translator;
  }
}
