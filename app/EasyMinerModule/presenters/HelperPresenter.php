<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\HelperRepository;
use App\Presenters\BaseRestPresenter;
use Nette\Application\ForbiddenRequestException;

/**
 * Class HelperPresenter - presenter s pomocnými akcemi pro EasyMiner
 * @package App\EasyMinerModule\Presenters
 */
class HelperPresenter extends BaseRestPresenter{

  /** @var  \App\Model\EasyMiner\Repositories\HelperRepository $helperRepository */
  private $helperRepository;

  /**
   * Akce pro uložení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   * @param string $data
   */
  public function actionSaveData($miner,$type,$data){
    $this->checkMinerAccess($miner);
    $this->helperRepository->saveData($miner,$type,$data);
    $this->sendJsonResponse(array('result'=>'ok','kbi'=>$miner,/*'user'=>0,*/'type'=>$type,'data'=>$data));
  }

  /**
   * Akce pro načtení pracovních dat EasyMineru
   * @param string $miner
   * @param string $type
   */
  public function actionLoadData($miner,$type){
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
    $this->helperRepository->deleteData($miner,$type);
    $this->sendJsonResponse(array('result'=>'ok','kbi'=>$miner,/*'user'=>0,*/'type'=>$type));
  }


  /**
   * @param string $miner
   * @throws \Nette\Application\ForbiddenRequestException
   */
  private function checkMinerAccess($miner){
    return true;
    //TODO kontrola, jestli má uživatel přístup k datům daného EasyMineru
    throw new ForbiddenRequestException();
  }

  public function injectHelperRepository(\App\Model\EasyMiner\Repositories\HelperRepository $helperRepository){
    $this->helperRepository=$helperRepository;
  }

} 