<?php

namespace EasyMinerCenter\EasyMinerModule\Router;

use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use Nette\Utils\Strings;


/**
 * Router factory for InstallModule
 * @author Stanislav Vojíř
 */
class RouterFactory {

  const BASE_PATH = "em/";

	/**
   * @param string $basePath = self::BASE_PATH
   * @param bool $secured=false
   * @return IRouter
   */
	public static function createRouter($basePath=self::BASE_PATH, $secured=false) {
    $dataMiningRouter = new RouteList('EasyMiner');
    $dataMiningRouter[] = new Route($basePath.'user/oauth-[!<type=google>]', ['presenter' => 'User', 'action' => 'oauthGoogle', null => array(Route::FILTER_IN => function (array $params) {
      $params['do'] = $params['type'] . 'Login-response';
      unset($params['type']);

      return $params;
    }, Route::FILTER_OUT => function (array $params) {
      if (empty($params['do']) || !preg_match('~^login\\-([^-]+)\\-response$~', $params['do'], $m)) {
        return null;
      }

      $params['type'] = Strings::lower($m[1]);
      unset($params['do']);

      return $params;
    },),], ($secured?Route::SECURED:0));
    $dataMiningRouter[] = new Route($basePath.'<presenter>[/<action=default>[/<id>]]', [], ($secured?Route::SECURED:0));
    return $dataMiningRouter;
  }

}
