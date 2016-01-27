<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;


use EasyMinerCenter\InstallModule\DevModule\model\DevConfigManager;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

/**
 * Class BasePresenter - základní kostra presenterů pro DEV submodul
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 */
abstract class BasePresenter extends Presenter{
  /** @var  DevConfigManager $devConfigManager */
  protected $devConfigManager;
  /** @persistent */
  public $username;
  /** @persistent */
  public $password;
  /** @var  bool $ignoreUserCheck=false */
  protected $ignoreUserCheck=false;

  /**
   * Funkce po spuštění, která kontroluje uživatelská oprávnění pro přístup k funkcionalitě DEV modulu
   */
  public function startup() {
    parent::startup();
    if (!$this->ignoreUserCheck){
      if (!$this->devConfigManager->checkUserCredentials($this->username, $this->password)){
        $this->flashMessage('You are not allowed to access the selected resources!','error');
        $this->redirect('Default:default');
      }
    }
  }
  /**
   * @param DevConfigManager $devConfigManager
   */
  public function injectDevConfigManager(DevConfigManager $devConfigManager) {
    $this->devConfigManager=$devConfigManager;
  }

}