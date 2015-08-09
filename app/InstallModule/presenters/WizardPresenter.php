<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use EasyMinerCenter\InstallModule\Model\FilesManager;
use Nette\Application\UI\Presenter;
use Nette\Neon\Neon;

/**
 * Class WizardPresenter
 * @package EasyMinerCenter\InstallModule\Presenters
 */
class WizardPresenter extends Presenter{

  /**
   * Tento presenter nemá výchozí view => pøesmìrování na vhodný krok
   */
  public function actionDefault(){
    $this->redirect('files');
  }

  /**
   * Akce pro kontrolu pøístupù ke složkám a souborùm [STEP 1]
   */
  public function actionFiles(){
    $filesManager = new FilesManager(Neon::decode(file_get_contents(__DIR__.'/../data/files.neon')));
    $writableDirectories = $filesManager->checkWritableDirectories();
    $writableFiles = $filesManager->checkWritableFiles();
    $stateError = false;
    $statesArr = [];
    if (!empty($writableDirectories)){
      foreach ($writableDirectories as $directory=>$state){
        $statesArr['Directory: '.$directory]=$state;
        if (!$state){$stateError=true;}
      }
    }
    if (!empty($writableFiles)){
      foreach ($writableFiles as $file=>$state){
        $statesArr['File: '.$file]=$state;
        if (!$state){$stateError=true;}
      }
    }

    if ($stateError || true){
      $this->setView('statesTable');
      $this->template->title='Writable files and directories';
      $this->template->text='Following files and directories has to been created and writable. If you are not sure in user access credentials, please modify them to 777. The paths are written from the content root, where the EasyMinerCenter is located.';
      $this->template->states=$statesArr;
    }else{
      $this->redirect('');
    }
  }

  /**
   * Akce pro zadání pøístupu k databázi [STEP 2]
   */
  public function actionDatabase() {
    //TODO
  }

  /**
   * Akce pro zadání zpùsobù pøihlašování [STEP 3]
   */
  public function actionLogins() {
    //TODO
  }

  /**
   * Akce pro volbu podporovaných typù minerù [STEP 4]
   */
  public function actionMiners() {
    //TODO
  }

  /**
   * Akce pro ukonèení prùvodce - smazání cache, pøesmìrování [STEP 5]
   */
  public function actionFinish() {
    //TODO
  }
}