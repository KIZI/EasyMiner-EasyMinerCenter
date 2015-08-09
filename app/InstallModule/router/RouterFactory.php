<?php

namespace EasyMinerCenter\InstallModule\Router;

use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


/**
 * Router factory for InstallModule
 * @author Stanislav Vojíø
 */
class RouterFactory {

  const BASE_PATH = "install";

	/**
   * @param string $baseUrl = self::BASE_PATH
   * @return IRouter
   */
	public static function createRouter($baseUrl=self::BASE_PATH) {
    $installRouter = new RouteList('Install');
    $installRouter[] = new Route($baseUrl.'<presenter=Default>[/<action=default>]');
    return $installRouter;
  }

}
