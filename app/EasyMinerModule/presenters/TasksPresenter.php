<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 15.9.14
 * Time: 23:35
 */

namespace App\EasyMinerModule\Presenters;


class TasksPresenter {

  /**
   * Akce pro spuštění dolování
   * @param string $miner
   * @param string $data
   */
  public function actionStartMining($miner,$data){
    //TODO import zadání úlohy a vrácení výsledků
  }

  /**
   * Akce pro zastavení dolování
   * @param string $miner
   * @param string $data
   */
  public function actionStopMining($miner,$data){
    //TODO zastavení dolování
  }

  /**
   * Akce vracející pravidla pro vykreslení v easymineru
   * @param $miner
   * @param $data
   * @param $start
   * @param $count
   */
  public function actionGetRules($miner,$data,$start,$count){
    //TODO akce pro vrácení části výsledků
  }

} 