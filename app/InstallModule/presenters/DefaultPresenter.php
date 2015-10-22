<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use EasyMinerCenter\InstallModule\Model\PhpConfigManager;
use Nette\Application\UI\Presenter;

/**
 * Class DefaultPresenter
 * @package EasyMinerCenter\InstallModule\Presenters
 */
class DefaultPresenter extends Presenter{

  /**
   * Akce pro zobrazení homepage instalátoru
   */
  public function renderDefault(){
    //kontrola verze PHP
    $phpMinVersion=PhpConfigManager::getPhpMinVersion();
    $this->template->phpVersion=$phpMinVersion;
    $this->template->phpVersionCheck=PhpConfigManager::checkPhpMinVersion($phpMinVersion);
  }

}