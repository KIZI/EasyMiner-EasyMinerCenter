<?php

namespace App;

use Drahak\Restful\Application\IResourceRouter;
use Drahak\Restful\Application\Routes\CrudRoute;
use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{
  const REST_MODULE_BASE_URL='rest/';

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
    $router[] = $knowledgeBaseRouter = new RouteList('KnowledgeBase');
    //$knowledgeBaseRouter[] = new Route('kb/<presenter>/<action>[/<id>]');
    $knowledgeBaseRouter[] = new Route('kb/<presenter>/<action>');

    $router[] = $dataMiningRouter = new RouteList('EasyMiner');
    $dataMiningRouter[] = new Route('em/user/oauth-[!<type=google>]', [
        'presenter' => 'User',
        'action' => 'oauthGoogle',
        NULL => array(
            Route::FILTER_IN => function (array $params) {
                $params['do'] = $params['type'] . 'Login-response';
                unset($params['type']);
    
                return $params;
            },
            Route::FILTER_OUT => function (array $params) {
                if (empty($params['do']) || !preg_match('~^login\\-([^-]+)\\-response$~', $params['do'], $m)) {
                    return NULL;
                }
    
                $params['type'] = \Nette\Utils\Strings::lower($m[1]);
                unset($params['do']);
    
                return $params;
            },
        ),
    ]);
    $dataMiningRouter[] = new Route('em/<presenter>/<action>[/<id>]');
    #region router pro RestModule

    $router[] = $restRouter = new RouteList('Rest');
    $restRouter[] = new Route(self::REST_MODULE_BASE_URL.'auth[/<action=default>]',['presenter'=>'Auth']);
    $restRouter[] = new CrudRoute(self::REST_MODULE_BASE_URL.'<presenter>[/<id>[/<relation>[/<relationId>]]]', [], IResourceRouter::GET | IResourceRouter::POST | IResourceRouter::PUT | IResourceRouter::DELETE);

    #endregion

    //$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}

}
