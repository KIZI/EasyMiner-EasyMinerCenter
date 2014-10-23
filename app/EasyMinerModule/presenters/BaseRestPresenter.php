<?php

namespace App\EasyMinerModule\Presenters;

use Nette\Application\ForbiddenRequestException;

abstract class BaseRestPresenter extends \App\Presenters\BaseRestPresenter{

  /**
   * @param string $miner
   * @throws ForbiddenRequestException
   * @return bool
   */
  protected function checkMinerAccess($miner){
    return true;
    //TODO kontrola, jestli má uživatel přístup k datům daného EasyMineru
    throw new ForbiddenRequestException();
  }

  protected function checkDatasourceAccess($datasource){
    return true;
    //TODO kontrola, jesli má aktuální uživatel právo přistupovat k datovému zdroji
  }
} 