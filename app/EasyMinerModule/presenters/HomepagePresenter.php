<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

/**
 * Class HomepagePresenter
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class HomepagePresenter extends BasePresenter{

  /**
   * Akce pro výchozí zobrazení homepage
   */
  public function renderDefault() {

  }

  public function actionTest() {
    $miner=$this->minersFacade->findMiner(18/*17*/);
    //TODO testování
    var_dump($this->user->isAllowed($miner,'edit'));
    $this->terminate();
  }
}