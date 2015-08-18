<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use EasyMinerCenter\InstallModule\Model\FilesManager;
use Nette\Application\UI\Presenter;
use Nette\Utils\Json;

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
    $composerConfig=Json::decode(file_get_contents(FilesManager::getRootDirectory().'/composer.json'),Json::FORCE_ARRAY);
    $phpVersion=$composerConfig['require']['php'];
    $phpVersion=ltrim($phpVersion,'>=~ ');
    $this->template->phpVersion=$phpVersion;
    $this->template->phpVersionCheck=version_compare(PHP_VERSION,$phpVersion,'>=');
  }

}