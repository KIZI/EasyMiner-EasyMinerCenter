<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

/**
 * Class CachePresenter - DEV presenter pro práci s cache
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 */
class CachePresenter extends BasePresenter{

  /**
   * Akce pro smazání obsahu adresáře, do kterého se ukládá aplikační cache
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