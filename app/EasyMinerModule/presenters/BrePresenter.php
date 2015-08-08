<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMiner\BRE\Integration as BREIntegration;


/**
 * Class BrePresenter - presenter obsahující funkcionalitu pro integraci submodulu EasyMiner-BRE
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class BrePresenter extends BasePresenter{
  /**
   * Akce pro zobrazení EasyMiner-BRE
   */
  public function renderDefault(){
    $this->template->javascriptFiles=BREIntegration::$javascriptFiles;
    $this->template->cssFiles=BREIntegration::$cssFiles;
    $this->template->content=BREIntegration::getContent();
  }

} 