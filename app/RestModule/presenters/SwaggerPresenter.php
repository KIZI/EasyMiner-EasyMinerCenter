<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Http\Url;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class SwaggerPresenter - presenter for displaying of Swagger UI
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class SwaggerPresenter extends Presenter{

  /**
   * Action for rendering of Swagger UI
   */
  public function renderUi(){
    /** @noinspection PhpUndefinedFieldInspection */
    $this->template->apiUrl=$this->link("json");
  }

  /**
   * Action for rendering of examples of preprocessing
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
   * Action returning JSON configuration for Swagger UI
   */
  public function actionJson() {
    //prepare swagger JSON
    $swaggerJson=\Swagger\scan(__DIR__);
    //replace used constants
    $swaggerJson=$this->replaceJsonVariables($swaggerJson);

    $httpResponse = $this->presenter->getHttpResponse();
    $httpResponse->setContentType('application/json');
    $httpResponse->setHeader('Content-Disposition', 'inline; filename="swagger.json"');
    $httpResponse->setHeader('Content-Length', strlen($swaggerJson));
    echo $swaggerJson;
    $this->terminate();
  }


  /**
   * Private method for filling in of basic params into config JSON
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
   * Private method returning info about the version of EasyMiner
   * @return string
   */
  private function getInstallVersion() {
    $installParameters=$this->context->getParameters();
    return @$installParameters['install']['version'];
  }

  /**
   * Startup method, we use it to prevent canonicalization of request URLs (due to addressing of RESTful API)
   */
  public function startup(){
    parent::startup();
    $this->autoCanonicalize = true;
  }
}