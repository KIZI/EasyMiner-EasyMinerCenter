<?php
/**
 * User: Stanislav Vojíř
 * Date: 22.9.14
 * Time: 18:08
 */

namespace EasyMinerCenter\EasyMinerModule\Presenters;


use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Presenters\BaseRestPresenter;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

abstract class BasePresenter extends BaseRestPresenter{
  /** @var  MinersFacade $minersFacade */
  protected $minersFacade;

  /**
   * @param Miner|int $miner
   * @throws ForbiddenRequestException
   * @return bool
   */
  protected function checkMinerAccess($miner){
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
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

  protected function checkDatasourceAccess($datasource){
    return true;
    //TODO kontrola, jesli má aktuální uživatel právo přistupovat k datovému zdroji
  }

  /**
   * @param MinersFacade $minersFacade
   */
  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
  }
} 