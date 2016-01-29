<?php

namespace MenuManager\Provider;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Provider\Routing;
use MenuManager\MenuManager\Item;
use MenuManager\MenuManager;

class MenuServiceProvider implements ServiceProviderInterface {

	public function register(Container $app) {
		$app['menu_manager'] = function ($app) {
			return new MenuManager($app, $app['menu']);
		};
		
		$app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($filesystem, $app) {
			$filesystem->addPath(__DIR__ . '/../Resource/views', 'defaultMenu');
			
			return $filesystem;
		});
		
		$app['twig'] = $app->extend('twig', function ($twig, $app) {
			$twig->addFunction(new \Twig_SimpleFunction('menu', function ($which = 'main', Item $item = null, $paramList = [], $activeOnly = false) use($app) {
				return $app['menu_manager']->render($which, $item, $paramList, $activeOnly);
			}));
			
			$twig->addFunction(new \Twig_SimpleFunction('breadcrumb', function () use($app) {
				return $app['menu_manager']->renderBreadcrumb();
			}));
			
			return $twig;
		});
	}
}