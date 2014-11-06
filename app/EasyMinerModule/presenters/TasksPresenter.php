<?php

namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Facades\MinersFacade;

class TasksPresenter  extends BasePresenter{

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
   */
  public function actionStopMining($miner, $task){
    #region pro testy
    if ($miner === 'TEST') {
      $this->sendJsonResponse(['status' => 'ok']);
    }
    #endregion
    //nalezení daného mineru a kontrola oprávnění uživatele pro přístup k němu
    $miner=$this->minersFacade->findMiner($miner);
    $this->checkMinerAccess($miner);

    //TODO zastavení dolování

    /***********************************************************************************************/
    /* { // KBI
      $requestData = ['pooler' => $taskMode];

      // run task
      $config = array(
        'source' => intval($id),
        'query' => '',
        'xslt' => NULL,
        'parameters' => NULL
      );

      $model = new KbiModelTransformator($config);
      $document = $model->cancelQuery($taskId);

      $ok = (strpos($document, 'kbierror') === false && !preg_match('/status=\"failure\"/', $document));

      if (FB_ENABLED && $debug) { // log into console
        FB::info(['curl request' => $requestData]);
        FB::info(['response' => $document]);
      }

      if (strpos($document, 'kbierror') === false && !preg_match('/status=\"failure\"/', $document)) {
        $success = preg_match('/status=\"success\"/', $document);
        if ($success) {
          $responseContent = ['status' => 'ok'];
        } else {
          $responseContent = ['status' => 'error'];
        }
      } else {
        returnError:
        $responseContent = ['status' => 'error'];
      }
    }*/


/***********************************************************************************************/

    $this->terminate();
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



  #region injections

  #endregion injections
} 