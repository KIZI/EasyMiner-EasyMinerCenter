<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Security\UnauthorizedRequestException;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;

/**
 * Trait MinersFacadeTrait
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\RestModule\Presenters
 *
 * @method User getCurrentUser()
 * @method error($message = null, $code = 404)
 */
trait MinersFacadeTrait {
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;


  /**
   * Metoda pro nalezení příslušného mineru s kontrolou přístupu
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
   * Metoda pro kontrolu oprávněnosti přistupovat ke zvolenému mineru
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