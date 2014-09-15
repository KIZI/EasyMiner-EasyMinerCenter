<?php

namespace App\EasyMinerModule\Presenters;

/**
 * Class DataPresenter - presenter pro práci s daty (import, zobrazování, smazání...)
 * @package App\EasyMinerModule\Presenters
 */
class DataPresenter {

  /**
   * Akce pro import dat z nahraného souboru/externí DB
   */
  public function renderImportData(){
    //TODO
  }

  /**
   * Akce pro smazání konkrétního mineru
   * @param $miner
   */
  public function renderDelete($miner){
    //TODO
  }

  public function renderAttributeHistogram(){
    //TODO vykreslení histogramu pro konkrétní atribut
  }

  public function renderColumnHistogram(){
    //TODO vykreslení histogramu pro konkrétní datový sloupec
  }

} 