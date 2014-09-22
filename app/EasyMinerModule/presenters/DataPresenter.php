<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;
use App\Model\EasyMiner\Repositories\MinersRepository;
use App\Presenters\BasePresenter;

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
   * Akce pro založení nového EasyMineru
   */
  public function renderNewMiner(){
    //TODO
  }

  /**
   * Akce pro import dat z nahraného souboru/externí DB
   */
  public function renderImportData($minerName){
    //TODO
  }

  /**
   * Akce pro smazání konkrétního mineru
   * @param int $miner
   */
  public function renderDeleteMiner($miner){
    //TODO
  }

  public function renderAttributeHistogram(){
    //TODO vykreslení histogramu pro konkrétní atribut
  }

  public function renderColumnHistogram(){
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