<?php

namespace Plumillon\MenuManager\MenuManager;

use Pimple\Container;

class Item extends MenuAbstract {
	public $path;
	public $url;
	public $text;
	protected $subList = [];

	public function init(Array $optionList) {
		$this->initBulkConfig($optionList, [
			'url',
			'text',
			'path'
		]);
		
		if(isset($optionList['subs']) && is_array($optionList['subs']))
			foreach($optionList['subs'] as $subMenuConfig) {
				if(!isset($subMenuConfig['breadcrumbTemplate']))
					$subMenuConfig['breadcrumbTemplate'] = $this->breadcrumbTemplate;
				
				if(isset($optionList['params']))
					$subMenuConfig['params'] = $optionList['params'];
				
				$subMenu = new Menu($this->app, $subMenuConfig, $this);
				$this->subList[] = $subMenu;
			}
	}

	public function initParamList() {
		foreach($this->subList as $subMenu)
			$subMenu->setParamList($this->paramList);
	}

	public function render() {
		$this->paramList = $this->getAllowedParamList($this->paramList);
		
		if($this->path != null) {
			// Override given URL with the corresponding path
			$this->url = $this->app['url_generator']->generate($this->path, $this->paramList);
			// $this->setActive($this->app['request']->getRequestUri() == $this->url);
		} else {
			if(!empty($this->paramList)) {
				$urlParamList = $this->paramList;
				array_walk($urlParamList, function (&$item, $key) {
					$item = $key . '=' . $item;
				});
				$urlParam = implode('&', $urlParamList);
				$this->url .= '?' . $urlParam;
			}
		}
		
		return $this->app['twig']->render($this->template, [
			'item' => $this
		]);
	}

	public function initActive() {
		$currentParamList = array_merge($this->app['request']->attributes->get('_route_params'), $this->app['request']->query->all());
		$potentialParamList = $this->getAllowedParamList($currentParamList);
		
		// var_dump($this->app['request']->getRequestUri());
		$this->setActive(isset($this->path) && $this->app['menu_manager']->getCurrentRoute() == $this->path && $potentialParamList == $currentParamList);
		
		foreach($this->subList as $subMenu)
			$subMenu->initActive();
		
		if($this->isActive)
			$this->app['menu_manager']->setActiveItem($this);
	}

	public function renderBreadcrumb($paramList = []) {
		foreach($this->getBreadcrumb() as $breadcrumb) {
			$breadcrumb->paramList = $breadcrumb->getAllowedParamList($paramList);
			
			if($breadcrumb->path != null) {
				// Override given URL with the corresponding path
				$breadcrumb->url = $this->app['url_generator']->generate($breadcrumb->path, $breadcrumb->paramList);
			}
		}
		
		return $this->app['twig']->render($this->breadcrumbTemplate, [
			'breadcrumbList' => array_reverse($this->getBreadcrumb())
		]);
	}

	private function getAllowedParamList($paramList = []) {
		if($this->allowAllParams)
			return $paramList;
		
		if($this->path != null) {
			$route = ($this->app['routes'] != null ? $this->app['routes']->get($this->path) : null);
			
			if($route != null) {
				// Alowed params
				$routeParamList = $route->compile()->getVariables();
				$routeParamList = array_merge($routeParamList, $this->allowedParamList);
				
				if(empty($routeParamList))
					$paramList = [];
				else
					foreach($paramList as $key => $param)
						if(!in_array($key, $routeParamList))
							unset($paramList[$key]);
			} else
				throw new MenuManagerException('No route found for ' . $this->path);
		} else
			foreach($paramList as $key => $param)
				if(!in_array($key, $this->allowedParamList))
					unset($paramList[$key]);
		
		return $paramList;
	}

	public function getSubList() {
		return $this->subList;
	}
}