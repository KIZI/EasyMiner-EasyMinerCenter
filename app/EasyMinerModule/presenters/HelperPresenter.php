<?php

namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Facades\HelperFacade;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Presenters\BaseRestPresenter;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class HelperPresenter - presenter s pomocnými akcemi pro EasyMiner
 * @package App\EasyMinerModule\Presenters
 */
class HelperPresenter extends BaseRestPresenter{

  /** @var  \App\Model\EasyMiner\Facades\HelperFacade $helperFacade */
  private $helperFacade;
  /** @var  \App\Model\EasyMiner\Facades\MinersFacade $minersFacade */
  private $minersFacade;

  /**
   * Akce pro uložení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   * @param string $data
   * @throws ForbiddenRequestException
   */
  public function actionSaveData($miner,$type,$data){
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
    $this->helperFacade->saveHelperData($miner,$type,$data);
    $this->sendJsonResponse(array('result'=>'ok','miner'=>$miner,'type'=>$type,'data'=>$data));
  }

  /**
   * Akce pro načtení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   * @throws ForbiddenRequestException
   * @throws BadRequestException
   */
  public function actionLoadData($miner,$type){
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
    try{
      $data=$this->helperFacade->loadHelperData($miner,$type);//TODO není potřeba dekódovat JSON?
      $this->sendJsonResponse(array('result'=>'ok','miner'=>$miner,'type'=>$type,'data'=>$data));
    }catch (\Exception $e){
      $this->sendJsonResponse(array('result'=>'error'));
      //throw new BadRequestException($this->translate('Requested data not found!'));
    }
  }

  /**
   * Akce pro smazání uložených dat
   * @param string $miner
   * @param string $type
   * @throws ForbiddenRequestException
   */
  public function actionDeleteData($miner,$type){
    if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
      throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
    }
    try{
      $this->helperFacade->deleteHelperData($miner,$type);
      $this->sendJsonResponse(array('result'=>'ok','miner'=>$miner,'type'=>$type));
    }catch (\Exception $e){
      $this->sendJsonResponse(array('result'=>'error'));
    }
  }

  #region injections
  public function injectHelperFacade(HelperFacade $helperFacade){
    $this->helperFacade=$helperFacade;
  }

  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
  }
  #endregion injections
} 