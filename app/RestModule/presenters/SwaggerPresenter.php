<?php

namespace EasyMinerCenter\RestModule\Presenters;


use Nette\Application\UI\Presenter;
use Nette\Http\Url;
use Nette\Utils\Strings;

class SwaggerPresenter extends Presenter{

  /**
   * Akce pro vykreslení Swagger UI
   */
  public function renderUi(){
    $this->template->apiUrl=$this->link("json");
  }

  /**
   * Akce pro vygenerování dokumentace
   */
  public function actionJson() {
    //připravení JSON specifikace
    $swaggerJson=\Swagger\scan(__DIR__);
    //nahrazení použitých konstant
    $swaggerJson=$this->replaceJsonVariables($swaggerJson);

    $httpResponse = $this->presenter->getHttpResponse();
    $httpResponse->setContentType('application/json');
    $httpResponse->setHeader('Content-Disposition', 'inline; filename="swagger.json"');
    $httpResponse->setHeader('Content-Length', strlen($swaggerJson));
    echo $swaggerJson;
    $this->terminate();
  }


  /**
   * Funkce pro doplnění základních parametrů do API
   * @param string $jsonString
   * @return string
   */
  private function replaceJsonVariables($jsonString){
    $link=$this->link('//Default:default');
    $url=new Url($link);
    if (empty($url->host)){
      $url=$this->getHttpRequest()->getUrl()->hostUrl;
      if (Strings::endsWith($url,'/')){rtrim($url,'/');}
      $url.=$link;
      $url=new Url($url);
    }
    $hostUrl=(Strings::endsWith($url->getHost(),'/')?rtrim($url->getHost(),'/'):$url->getHost());
    $basePath=rtrim($url->getBasePath(),'/');
    $paramsArr=[
      '%VERSION%'=>'1.0',//TODO dynamické doplnění verze
      '%BASE_PATH%'=>$basePath,
      '%HOST%'=>$hostUrl
    ];
    $arrSearch=[];
    $arrReplace=[];
    foreach($paramsArr as $key=>$value){
      $arrSearch[]=$key;
      $arrReplace[]=$value;
    }
    return str_replace($arrSearch,$arrReplace,$jsonString);
  }

  /**
   * Startup funkce, použijeme ji pro zakázání kanonizace odkazů (kvůli adresování RESTFUL API)
   */
  public function startup(){
    parent::startup();
    //Debugger::enable(Debugger::PRODUCTION);
    $this->autoCanonicalize = true;
  }
}