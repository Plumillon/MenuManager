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
				
				$subMenu = new Menu($this->app, $subMenuConfig);
				$subMenu->setParent($this);
				$this->subList[] = $subMenu;
			}
	}

	public function initParamList() {
		foreach($this->subList as $subMenu)
			$subMenu->setParamList($this->paramList);
	}

	public function render() {
		return $this->app['twig']->render($this->template, [
			'item' => $this
		]);
	}

	public function generate() {
		if($this->path != null) {
			$route = ($this->app['routes'] != null ? $this->app['routes']->get($this->path) : null);
			
			if($route != null) {
				if(!$this->allowAllParams) {
					// Alowed params
					$routeParamList = $route->compile()->getVariables();
					$routeParamList = array_merge($routeParamList, $this->allowedParamList);
					
					if(empty($routeParamList))
						$this->paramList = [];
					else
						foreach($this->paramList as $key => $param)
							if(!in_array($key, $routeParamList))
								unset($this->paramList[$key]);
				}
				
				// Override given URL with the corresponding path
				$this->url = $this->app['url_generator']->generate($this->path, $this->paramList);
				$this->setActive($this->app['request']->getRequestUri() == $this->url);
				// $this->setActive($this->app['request']->get('_route') == $this->path);
			} else
				throw new MenuManagerException('No route found for ' . $this->path);
		} else {
			$urlParam = $this->getAllowedParamList();
			
			if(!empty($urlParam))
				$this->url .= '?' . $urlParam;
		}
		
		foreach($this->subList as $subMenu)
			$subMenu->generate();
		
		if($this->isActive)
			$this->app['menu_manager']->addActiveItem($this);
	}

	public function renderBreadcrumb() {
		return $this->app['twig']->render($this->breadcrumbTemplate, [
			'breadcrumbList' => array_reverse($this->getBreadcrumb())
		]);
	}

	private function getAllowedParamList() {
		$urlParamList = [];
		
		foreach($this->paramList as $key => $param)
			if($this->allowAllParams || in_array($key, $this->allowedParamList))
				$urlParamList[] = $key . '=' . $param;
		
		return implode('&', $urlParamList);
	}

	public function getSubList() {
		return $this->subList;
	}
}