<?php

namespace EasyMinerCenter\InstallModule\Router;

use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use Nette\Http\Request;


/**
 * Router factory for InstallModule
 * @author Stanislav Vojíř
 */
class RouterFactory {

  const BASE_PATH = "install/";

  /**
   * @param string $basePath = self::BASE_PATH
   * @param Request $request
   * @return IRouter
   */
	public static function createRouter($basePath=self::BASE_PATH, Request $request) {
    $secured=$request->isSecured();
    $installRouter = new RouteList('Install');
    $installRouter[] = $devSubmoduleRouter = new RouteList('Dev');
    $devSubmoduleRouter[] = new Route($basePath.'dev/<presenter=Default>[/<action=default>]',[],($secured?Route::SECURED:0));
    $installRouter[] = new Route($basePath.'<presenter=Default>[/<action=default>]',[],($secured?Route::SECURED:0));
    return $installRouter;
  }

}
