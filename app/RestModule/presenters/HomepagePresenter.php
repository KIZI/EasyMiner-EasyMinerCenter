<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Utils\Strings;
use Tracy\Debugger;

/**
 * Class HomepagePresenter - UI presenter pro zprovoznění swagger přístupu k API
 * @package EasyMinerCenter\RestModule\Presenters
 */
class HomepagePresenter extends Presenter{

  public function actionRead($f=''){
    if (!empty($f)){
      //chceme vracet konkrétní soubor
      $this->sendApiJsonFile($f);
      return;
    }

    $this->template->apiUrl=$this->getApiLink().'?f=api-docs.json';
  }

  private function sendApiJsonFile($fileName){
    if (!file_exists(__DIR__.'/../swagger/'.$fileName)&&strpos($fileName,'/')){
      $fileNameArr=explode('/',$fileName);
      $fileName=$fileNameArr[count($fileNameArr)-1];
    }

    //$this->sendResponse(new FileResponse(__DIR__.'/../swagger/'.$fileName,$fileName,'application/json'));return;

    $content=file_get_contents(__DIR__.'/../swagger/'.$fileName);
    $content=str_replace('BASE_PATH',$this->getApiLink(),$content);

    $httpResponse = $this->presenter->getHttpResponse();
    $httpResponse->setContentType('application/json');
    $httpResponse->setHeader('Content-Disposition', 'inline; filename="'.$fileName.'"');
    $httpResponse->setHeader('Content-Length', strlen($content));
    echo $content;
    $this->terminate();
  }

  /**
   * Funkce vracející URL REST API
   * @return string
   */
  private function getApiLink(){
    $link=$this->link('read');
    if (Strings::endsWith($link,'homepage')){
      $link=Strings::substring($link,0,Strings::length($link)-8);
    }
    $link=$this->getHttpRequest()->getUrl()->hostUrl.$link;
    $link=rtrim($link,'/');
    return $link;
  }

  /**
   * Startup funkce, použijeme ji pro zakázání kanonizace odkazů (kvůli adresování RESTFUL API)
   */
  public function startup(){
    parent::startup();
    Debugger::enable(Debugger::PRODUCTION);
    $this->autoCanonicalize = false;
  }
}