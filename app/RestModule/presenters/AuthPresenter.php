<?php
namespace App\RestModule\Presenters;

use App\Libs\StringsHelper;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\UI\Presenter;


class AuthPresenter extends Presenter {

  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  /**
   * @param string $key
   */
  public function actionDefault($key){

    $this->terminate();
    //$this->resource=['state'=>'ok'];
    //$this->sendResource();
  }

  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

}