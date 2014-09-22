<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;
use App\Model\EasyMiner\Repositories\MinersRepository;
use App\EasyMinerModule\Presenters\BasePresenter;
use Nette\Application\BadRequestException;

/**
 * Class DataPresenter - presenter pro práci s daty (import, zobrazování, smazání...)
 * @package App\EasyMinerModule\Presenters
 */
class DataPresenter extends BasePresenter{

  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  MinersRepository $minersRepository */
  private $minersRepository;


  /**
   * Akce pro otevření existujícího mineru
   */
  public function renderOpenMiner($id){//TODO dodělat javascriptové přesměrování v šabloně
    $miner=$this->minersRepository->findMiner($id);
    if (!$miner){
      throw new BadRequestException($this->translate('Requested miner not found!'),404);
    }
    $this->checkMinerAccess($miner);
    $this->template->miner=$miner;
  }

  /**
   * Akce pro založení nového EasyMineru či otevření stávajícího
   */
  public function renderNewMiner(){
    if ($this->user->id){
      $this->template->miners=$this->minersRepository->findMinersByUser($this->user->id);
    }else{
      //pro anonymní uživatele nebudeme načítat existující minery
      $this->template->miners=null;
    }
  }

  /**
   * Akce pro import dat z nahraného souboru/externí DB
   */
  public function renderImportData($minerName){
    //TODO
  }

  /**
   * Akce pro smazání konkrétního mineru
   * @param int $id
   */
  public function renderDeleteMiner($id){
    $miner=$this->minersRepository->findMiner($id);
    $this->checkMinerAccess($miner);
    //TODO
  }

  public function renderAttributeHistogram($miner,$attribute){
    //TODO vykreslení histogramu pro konkrétní atribut
  }

  public function renderColumnHistogram($miner,$column){
    //TODO vykreslení histogramu pro konkrétní datový sloupec
  }

  #region injections
  /**
   * @param DatasourcesRepository $datasourcesRepository
   */
  public function injectDatasourceRepository(DatasourcesRepository $datasourcesRepository){
    $this->datasourcesRepository=$datasourcesRepository;
  }

  /**
   * @param MinersRepository $minersRepository
   */
  public function injectMinersRepository(MinersRepository $minersRepository){
    $this->minersRepository=$minersRepository;
  }
  #endregion
} 