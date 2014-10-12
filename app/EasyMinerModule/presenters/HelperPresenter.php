<?php

namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Facades\HelperFacade;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Presenters\BaseRestPresenter;
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
   */
  public function actionSaveData($miner,$type,$data){
    $this->checkMinerAccess($miner);
    $this->helperFacade->saveData($miner,$type,$data);
    $this->sendJsonResponse(array('result'=>'ok','kbi'=>$miner,/*'user'=>0,*/'type'=>$type,'data'=>$data));
  }

  /**
   * Akce pro načtení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   */
  public function actionLoadData($miner,$type){
    $this->minersFacade


    $this->checkMinerAccess($miner);
    $data=$this->helperRepository->loadData($miner,$type);
    $this->sendJsonResponse(array('result'=>'ok','kbi'=>$miner,/*'user'=>0,*/'type'=>$type,'data'=>$data));
  }

  /**
   * Akce pro smazání uložených dat
   * @param string $miner
   * @param string $type
   */
  public function actionDeleteData($miner,$type){
    $this->checkMinerAccess($miner);
    $this->helperFacade->deleteData($miner,$type);
    $this->sendJsonResponse(array('result'=>'ok','kbi'=>$miner,/*'user'=>0,*/'type'=>$type));
  }

  public function injectHelperFacade(HelperFacade $helperFacade){
    $this->helperFacade=$helperFacade;
  }

  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
  }

} 