<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Http\Url;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class SwaggerPresenter - presenter pro zobrazení Swagger UI
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 */
class SwaggerPresenter extends Presenter{

  /**
   * Akce pro vykreslení Swagger UI
   */
  public function renderUi(){
    /** @noinspection PhpUndefinedFieldInspection */
    $this->template->apiUrl=$this->link("json");
  }

  /**
   * Akce pro vypsání příkladů preprocessingu
   */
  public function renderExamples(){
    $subfiles=['.'=>[]];
    $subfilesDirectoryDescriptions=[];
    $directories=Finder::findDirectories('*')->from(__DIR__.'/../../../www/api-examples');
    if (!empty($directories)){
      foreach($directories as $directory){
        /** @var  \SplFileInfo $directory */
        $subfiles[$directory->getFilename()]=[];
      }
    }
    foreach($subfiles as $directory=>&$arr){
      $files=Finder::findFiles('*')->in(__DIR__.'/../../../www/api-examples/'.$directory);
      if (!empty($files)){
        foreach($files as $file){
          /** @var \SplFileInfo $file */
          $filename=$file->getFilename();
          if ($filename=='readme.txt'){
            $subfilesDirectoryDescriptions[$directory]=file_get_contents($file->getRealPath());
          }else{
            $arr[]=$filename;
          }
        }
      }
    }
    $this->template->exampleFiles=$subfiles;
    $this->template->exampleFilesDirectoryDescriptions=$subfilesDirectoryDescriptions;
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
      '%VERSION%'=>$this->getInstallVersion(),
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
   * Funkce vracející informaci o verzi aplikace
   * @return string
   */
  private function getInstallVersion() {
    $installParameters=$this->context->getParameters();
    return @$installParameters['install']['version'];
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