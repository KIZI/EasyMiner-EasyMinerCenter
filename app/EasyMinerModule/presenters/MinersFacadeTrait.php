<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;


use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\Translation\EasyMinerTranslator;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Security\User;

/**
 * Trait MinersFacadeTrait - kód pro přístup k minerům v rámci presenterů
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 *
 * @property EasyMinerTranslator $translator
 * @property User $user
 * @method bool isAjax()
 * @method flashMessage($text, $type)
 * @method redirect($code=null, $destination=null, $params=[])
 * @method storeRequest()
 */
trait MinersFacadeTrait {
  /** @var  MinersFacade $minersFacade */
  protected $minersFacade;

  /**
   * @param Miner|int $miner
   * @return bool
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  protected function checkMinerAccess($miner){
    if (!($miner instanceof Miner)){
      try{
        $miner=$this->minersFacade->findMiner($miner);
      }catch (\Exception $e){
        throw new BadRequestException('Requested miner not specified!', 404, $e);
      }
    }
    if (!$this->user->isAllowed($miner)){
      if (!$this->isAjax() && $this->user->isLoggedIn()){
        //pokud nejde o ajax a uživatel není přihlášen, přesměrujeme ho na přihlášení
        $this->flashMessage('For access to the required resource, you have to log in!','warn');
        $this->redirect('User:login',['backlink'=>$this->storeRequest()]);
      }else{
        throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
      }
    }
    return true;
  }

  /**
   * Funkce vracející instanci zvoleného mineru po kontrole, jestli má uživatel právo k němu přistupovat
   * @param int|Miner $miner
   * @return Miner
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  protected function findMinerWithCheckAccess($miner){
    if (!($miner instanceof Miner)){
      try{
        $miner=$this->minersFacade->findMiner($miner);
      }catch (\Exception $e){
        throw new BadRequestException('Requested miner not specified!', 404, $e);
      }
    }
    $this->checkMinerAccess($miner);
    return $miner;
  }

  #region injections
  /**
   * @param MinersFacade $minersFacade
   */
  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
  }
  #endregion injections

}