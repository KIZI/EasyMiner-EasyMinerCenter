<?php

namespace EasyMinerCenter;

use Nette, Nette\Application\Routers\RouteList, Nette\Application\Routers\Route;


/**
 * Router factory
 * @author Stanislav Vojíř
 */
class RouterFactory {
  const EASYMINER_MODULE_BASE_URL = 'em/';
  const REST_MODULE_BASE_URL = 'api/';
  const INSTALL_MODULE_BASE_URL = 'install/';

	/**
   * Funkce pro vygenerování kompletního routeru pro aplikaci
   * @param bool $secured
   * @return \Nette\Application\IRouter
   */
	public static function createRouter($secured=false) {
    $router = new RouteList();

    $router[] = new Route('', ['module' => 'EasyMiner', 'presenter' => 'Homepage', 'action' => 'default'], ($secured?Route::SECURED:0));
    $router[] = \EasyMinerCenter\EasyMinerModule\Router\RouterFactory::createRouter(self::EASYMINER_MODULE_BASE_URL, $secured);
    $router[] = \EasyMinerCenter\RestModule\Router\RouterFactory::createRouter(self::REST_MODULE_BASE_URL, $secured);
    $router[] = \EasyMinerCenter\InstallModule\Router\RouterFactory::createRouter(self::INSTALL_MODULE_BASE_URL, $secured);

    return $router;
  }

}
