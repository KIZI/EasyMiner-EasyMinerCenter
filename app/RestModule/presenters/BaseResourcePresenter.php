<?php
namespace App\RestModule\Presenters;

use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\Application\UI\SecuredResourcePresenter;
use Drahak\Restful\Http\IInput;
use Drahak\Restful\Validation\IDataProvider;

/**
 * Class BaseResourcePresenter
 * @package App\RestModule\Presenters
 * @property IInput|IDataProvider $input
 *
 * @SWG\Info(
 *   title="EasyMinerCenter REST API",
 *   description="Api for access to EasyMinerCenter functionalities - authentication of users, management of data sources",
 *   contact="stanislav.vojir@vse.cz",
 *   license="BSD3",
 * )
 *
 * @SWG\Authorization(
 *   type="apiKey",
 *   passAs="query",
 *   keyname="key"
 * )
 */
abstract class BaseResourcePresenter extends ResourcePresenter {
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;


  /**
   * Funkce vracející instanci aktuálně přihlášeného uživatele (buď dle přihlášení, nebo podle API KEY)
   * @return User
   */
  public function getCurrentUser(){
    if ($this->user->isLoggedIn()){
      try{
        return $this->usersFacade->findUser($this->user->id);
      }catch (\Exception $e){}
    }

    return null;
    //TODO implementovat kontrolu dle API KEY
  }


  #region injections
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  #endregion
}