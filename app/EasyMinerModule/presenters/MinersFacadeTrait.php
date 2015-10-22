<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;


use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Localization\ITranslator;
use Nette\Security\User;

/**
 * Trait MinersFacadeTrait - kód pro přístup k minerům v rámci presenterů
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @property ITranslator $translator
 * @property User $user
 */
trait MinersFacadeTrait {
  /** @var  MinersFacade $minersFacade */
  protected $minersFacade;

  /**
   * @param Miner|int $miner
   * @throws ForbiddenRequestException
   * @return bool
   */
  protected function checkMinerAccess($miner){
    if (!$this->user->isAllowed($miner)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
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