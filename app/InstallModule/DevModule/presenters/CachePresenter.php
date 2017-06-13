<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

/**
 * Class CachePresenter - DEV presenter for work with cache
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CachePresenter extends BasePresenter{

  /**
   * Action for deletion of CACHE directory
   * @throws \Nette\Application\AbortException
   */
  public function actionClean() {
    $deletedArr=[];
    $errorArr=[];
    foreach(Finder::find('*')->in(CACHE_DIRECTORY) as $file=>$info){
      try{
        FileSystem::delete($file);
        $deletedArr[]=$file;
      }catch (\Exception $e){
        $errorArr[]=$file;
      }
    }
    $response=[
      'state'=>(empty($errorArr)?'OK':'error'),
      'deleted'=>$deletedArr,
      'errors'=>$errorArr
    ];
    $this->sendJson($response);
  }

}