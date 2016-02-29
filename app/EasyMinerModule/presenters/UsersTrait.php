<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Security\User;

/**
 * Class UsersTrait
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 *
 * @property User $user - property převzatá z presenteru
 *
 */
trait UsersTrait {
  /** @var  UsersFacade $usersFacade */
  protected $usersFacade;


  /**
   * @return \EasyMinerCenter\Model\EasyMiner\Entities\User|null
   */
  private function getCurrentUser(){
    try{
      return $this->usersFacade->findUser($this->user->id);
    }catch (\Exception $e){
      /*ignore error (uživatel nemusí být přihlášen)*/
    }
    return null;
  }


  #region injections

  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

  #endregion injections

}