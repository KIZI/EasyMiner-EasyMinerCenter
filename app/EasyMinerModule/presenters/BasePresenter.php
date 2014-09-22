<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 22.9.14
 * Time: 18:08
 */

namespace App\EasyMinerModule\Presenters;


use Nette\Application\ForbiddenRequestException;

class BasePresenter extends \App\Presenters\BasePresenter{

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
} 