<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;

/**
 * Class BrePresenter - presenter with the functionality for integration of the submodule EasyMiner-BRE
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BrePresenter extends BasePresenter{

  /**
   * Action for display of EasyMiner-BRE
   */
  public function renderDefault(){
    $this->template->javascriptFiles=BREIntegration::$javascriptFiles;
    $this->template->cssFiles=BREIntegration::$cssFiles;
    $this->template->content=BREIntegration::getContent();
    $this->template->moduleName=BREIntegration::MODULE_NAME;
    $this->template->GET=json_encode($_GET);
  }

} 