<?php

namespace EasyMinerCenter\RestModule\Router;

use Drahak\Restful\Application\IResourceRouter;
use Drahak\Restful\Application\Routes\CrudRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

/**
 * Class RouterFactory - Router factory for RestModule
 * @package EasyMinerCenter\RestModule\Router
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RouterFactory {

  const BASE_PATH = 'api/';

	/**
   * @param string $basePath = self::BASE_PATH
   * @param bool $secured=false
   * @return IRouter
   */
	public static function createRouter($basePath=self::BASE_PATH, $secured=false) {
    $restRouter = new RouteList('Rest');
    $restRouter[] = new Route($basePath . 'auth[/<action=read>]', ['presenter' => 'Auth'], ($secured?Route::SECURED:0));
    $restRouter[] = new Route($basePath . 'swagger[/<action=ui>]', ['presenter' => 'Swagger'], ($secured?Route::SECURED:0));
    $routeFlags=IResourceRouter::GET | IResourceRouter::POST | IResourceRouter::PUT | IResourceRouter::DELETE;
    if ($secured){
      $routeFlags |= IResourceRouter::SECURED;
    }
    $restRouter[] = new CrudRoute($basePath . 'databases/<dbType>[/<id>[/<relation>[/<relationId>]]]', ['presenter'=>'Databases'], $routeFlags);
    $restRouter[] = new CrudRoute($basePath . '<presenter>[/<id>[/<relation>[/<relationId>]]]', [], $routeFlags);
    $restRouter[] = new Route($basePath, ['presenter' => 'Default', 'action' => 'default'], ($secured?Route::SECURED:0));
    return $restRouter;
  }

}
