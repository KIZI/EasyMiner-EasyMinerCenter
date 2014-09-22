<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 * @property-read \Nette\Bridges\ApplicationLatte\Template|\stdClass $template
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter{
  /** @var  Nette\Localization\ITranslator $translator */
  protected  $translator;

  function beforeRender(){
    /** @noinspection PhpUndefinedMethodInspection */
    $this->template->setTranslator($this->translator);
  }

  public function injectTranslator(Nette\Localization\ITranslator $translator){
    $this->translator=$translator;
  }

  /**
   * Funkce pro překlad pomocí výchozího translatoru
   * @param string $message
   * @param null|int $count
   * @return string
   */
  public function translate($message, $count=null){
    return $this->translator->translate($message,$count);
  }

}
