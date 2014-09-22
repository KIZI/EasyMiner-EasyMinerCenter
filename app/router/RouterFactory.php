<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();


    $router[] = $knowledgeBaseRouter = new RouteList('KnowledgeBase');
    $knowledgeBaseRouter[] = new Route('/kb/<presenter>/<action>[/<id>]');

    $router[] = $dataMiningRouter = new RouteList('EasyMiner');
    $dataMiningRouter[] = new Route('em/<presenter>/<action>[/<id>]');

    //$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}

}
