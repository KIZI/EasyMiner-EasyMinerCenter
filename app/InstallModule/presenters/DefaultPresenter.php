<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Localization\ITranslator;
use Nette\Neon\Neon;

/**
 * Class DefaultPresenter
 * @package EasyMinerCenter\InstallModule\Presenters
 */
class DefaultPresenter extends Presenter{
  /** @var  ITranslator $translator */
  protected  $translator;

  /**
   * V�choz� zobrazen�
   */
  public function renderDefault() {

  }




  #region injections
  /**
   * Funkce p�ed vyrenderov�n�m �ablony (nastav� translator)
   */
  protected function beforeRender(){
    $this->template->translator=$this->translator;
  }

  /**
   * @param ITranslator $translator
   */
  public function injectTranslator(ITranslator $translator){
    $this->template->translator=$translator;
  }

  #endregion injections
}