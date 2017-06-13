<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use EasyMinerCenter\InstallModule\Model\PhpConfigManager;
use Nette\Application\UI\Presenter;

/**
 * Class DefaultPresenter
 * @package EasyMinerCenter\InstallModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DefaultPresenter extends Presenter{

  /**
   * Action for rendering of the installer homepage
   */
  public function renderDefault(){
    //check PHP version
    $phpMinVersion=PhpConfigManager::getPhpMinVersion();
    $this->template->phpVersion=$phpMinVersion;
    $this->template->phpVersionCheck=PhpConfigManager::checkPhpMinVersion($phpMinVersion);
  }

}