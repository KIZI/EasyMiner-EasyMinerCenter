<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;

use EasyMinerCenter\InstallModule\DevModule\Model\DevConfigManager;
use Nette\Application\UI\Presenter;

/**
 * Class BasePresenter - base presenter for DEV module
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
   * Startup method, checks, if the current User can run the DEV module functionality
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