<?php

namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Facades\HelperFacade;
use App\Model\EasyMiner\Facades\MinersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * Class HelperPresenter - presenter s pomocnými akcemi pro EasyMiner
 * @package App\EasyMinerModule\Presenters
 */
class HelperPresenter extends BasePresenter{

  /** @var  \App\Model\EasyMiner\Facades\HelperFacade $helperFacade */
  private $helperFacade;

  /**
   * Akce pro uložení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   * @param string $data
   * @throws ForbiddenRequestException
   * @throws BadRequestException
   */
  public function actionSaveData($miner,$type,$data){
    $miner=$this->findMinerWithCheckAccess($miner);
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
    $miner=$this->findMinerWithCheckAccess($miner);
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
    $miner=$this->findMinerWithCheckAccess($miner);
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