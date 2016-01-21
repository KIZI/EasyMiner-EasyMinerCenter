<?php

namespace EasyMinerCenter\InstallModule\Router;

use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


/**
 * Router factory for InstallModule
 * @author Stanislav Vojíř
 */
class RouterFactory {

  const BASE_PATH = "install/";

	/**
   * @param string $basePath = self::BASE_PATH
   * @param bool $secured=false
   * @return IRouter
   */
	public static function createRouter($basePath=self::BASE_PATH, $secured=false) {
    $installRouter = new RouteList('Install');
    $installRouter[] = new Route($basePath.'<presenter=Default>[/<action=default>]',[],($secured?Route::SECURED:0));
    return $installRouter;
  }

}
