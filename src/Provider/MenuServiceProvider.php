<?php

namespace Plumillon\MenuManager\Provider;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Provider\Routing;
use Plumillon\MenuManager\MenuManager\Item;
use Plumillon\MenuManager\MenuManager;
use Plumillon\MenuManager\Service\MenuSecurityService;
use Symfony\Component\HttpFoundation\Request;

class MenuServiceProvider implements ServiceProviderInterface {

	public function register(Container $app) {
		$app['menu_manager'] = function ($app) {
			return new MenuManager($app, $app['menu']);
		};
		
		$app['menu_manager.security'] = function ($app) {
			return new MenuSecurityService($app);
		};
		
		$app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($filesystem, $app) {
			$filesystem->addPath(__DIR__ . '/../Resource/views', 'MenuManager');
			
			return $filesystem;
		});
		
		$app['twig'] = $app->extend('twig', function ($twig, $app) {
			$twig->addFunction(new \Twig_SimpleFunction('menu', function ($which = 'main', Item $item = null, $paramList = [], $activeOnly = false) use($app) {
				return $app['menu_manager']->render($which, $item, $paramList, $activeOnly);
			}));
			
			$twig->addFunction(new \Twig_SimpleFunction('breadcrumb', function ($paramList = []) use($app) {
				return $app['menu_manager']->renderBreadcrumb($paramList);
			}));
			
			return $twig;
		});
		
		// Current route name
		$app->before(function (Request $request) use($app) {
			$app['menu_manager']->setCurrentRoute($request->get('_route'));
		});
	}
}