<?php

namespace EasyMinerCenter;

use Nette, Nette\Application\Routers\RouteList, Nette\Application\Routers\Route;

/**
 * Class RouterFactory
 * @package EasyMinerCenter
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RouterFactory {
  const EASYMINER_MODULE_BASE_URL = 'em/';
  const REST_MODULE_BASE_URL = 'api/';
  const INSTALL_MODULE_BASE_URL = 'install/';

  /**
   * Static method returning list of routers for the full application
   * @param bool $secured
   * @param Nette\Http\Request $httpRequest
   * @return Nette\Application\IRouter
   */
	public static function createRouter($secured=false, Nette\Http\Request $httpRequest) {
    $router = new RouteList();
    $router[] = new Route('', ['module' => 'EasyMiner', 'presenter' => 'Homepage', 'action' => 'default'], ($secured?Route::SECURED:0));
    $router[] = \EasyMinerCenter\EasyMinerModule\Router\RouterFactory::createRouter(self::EASYMINER_MODULE_BASE_URL, $secured);
    $router[] = \EasyMinerCenter\RestModule\Router\RouterFactory::createRouter(self::REST_MODULE_BASE_URL, $secured);
    $router[] = \EasyMinerCenter\InstallModule\Router\RouterFactory::createRouter(self::INSTALL_MODULE_BASE_URL,$httpRequest);

    return $router;
  }

}
