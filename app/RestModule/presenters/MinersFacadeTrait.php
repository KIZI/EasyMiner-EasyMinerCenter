<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Security\UnauthorizedRequestException;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;

/**
 * Trait MinersFacadeTrait
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property MinersFacade $minersFacade
 * @method User getCurrentUser()
 * @method error($message = null, $code = 404)
 */
trait MinersFacadeTrait {
  /** @var  MinersFacade $minersFacade */
  protected $minersFacade;

  /**
   * Method for finding a miner by $minerId and checking of user privileges to work with the found miner
   * @param int $minerId
   * @return null|Miner
   * @throws \Nette\Application\BadRequestException
   */
  protected function findMinerWithCheckAccess($minerId){
    try{
      /** @var Miner $miner */
      $miner=$this->minersFacade->findMiner($minerId);
    }catch (\Exception $e){
      $this->error('Requested miner was not found.');
      return null;
    }
    $this->checkMinerAccess($miner);
    return $miner;
  }

  /**
   * Method for check of the user privileges to work with the selected miner
   * @param Miner|int|null $miner
   * @throws UnauthorizedRequestException
   */
  protected function checkMinerAccess($miner) {
    if(!$this->minersFacade->checkMinerAccess($miner, $this->getCurrentUser())) {
      throw new UnauthorizedRequestException('You are not authorized to use the selected miner!');
    }
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