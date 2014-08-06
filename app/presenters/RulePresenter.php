<?php

namespace App\Presenters;


class RulePresenter extends BaseRestPresenter{
  /**
   * Akce pro vypsání seznamu uložených pravidel
   * @param string $baseId = ''
   */
  public function actionList($baseId=''){
   //TODO
  }

  /**
   * Akce vracející jedno pravidlo ve formátu JSON
   * @param string $baseId = ''
   * @param string $uri
   */
  public function actionGet($baseId='',$uri){
    //TODO
  }

  /**
   * Akce pro uložení pravidla
   * @param string $baseId = ''
   * @param string $uri
   * @param string $data - pravidlo ve formátu JSON
   */
  public function actionSave($baseId='',$uri,$data){
    //TODO
  }
} 