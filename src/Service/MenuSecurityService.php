<?php

namespace Plumillon\MenuManager\Service;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Doit regrouper toutes les functions avec des règles de gestion.
 */
class MenuSecurityService {
	protected $app;
	protected $conditionList = [];

	public function __construct(Container $app) {
		$this->app = $app;
		$this->conditionList[] = function ($path, $method = 'GET') {
			$request = ($path instanceof Request ? $path : Request::create($path, $method));
			list($roleList, $channel) = $this->app['security.access_map']->getPatterns($request);
			
			if(empty($roleList))
				return true;
			
			if($this->app['security.token_storage']->getToken() != null)
				foreach($roleList as $role)
					if($this->app['security.authorization_checker']->isGranted($role))
						return true;
			
			return false;
		};
	}

	public function addCondition(\Closure $closure) {
		$this->conditionList[] = $closure;
	}

	public function isGranted($path, $method = 'GET') {
		foreach($this->conditionList as $condition)
			if(!$condition($path, $method))
				return false;
		
		return true;
	}
}
