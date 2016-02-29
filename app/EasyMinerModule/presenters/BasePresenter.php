<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Translation\EasyMinerTranslator;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;

/**
 * Class BasePresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @property \Nette\Bridges\ApplicationLatte\Template|\stdClass $template
 */
abstract class BasePresenter extends Presenter{//BaseRestPresenter

  #region translator
  /** @var  EasyMinerTranslator $translator */
  protected  $translator;

  protected function beforeRender(){
    /** @noinspection PhpUndefinedMethodInspection */
    $this->template->setTranslator($this->translator);
    $this->template->lang=$this->translator->getLang();
    $this->template->titleAppName='EasyMiner';
  }

  public function injectTranslator(EasyMinerTranslator $translator){
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

  public function getTranslator(){
    return $this->translator;
  }

  #endregion translator

  #region ACL
  /**
   * Metoda volaná při spuštění presenteru, v rámci které je řešeno oprávnění přístupu ke zvolenému zdroji
   */
  protected function startup() {
    $user=$this->getUser();
    $action=$this->request->parameters['action']?$this->request->parameters['action']:'';
    if (!$user->isAllowed($this->request->presenterName,$action)){
      if ($user->isLoggedIn()){
        throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access the required resource!'));
      }else{
        $this->flashMessage('For access to the required resource, you have to log in!','warn');
        $this->redirect('User:login',['backlink'=>$this->storeRequest()]);
      }
    }
    parent::startup();
  }
  #endregion ACL

} 