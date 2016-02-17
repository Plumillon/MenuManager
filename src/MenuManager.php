<?php

namespace Plumillon\MenuManager;

use Pimple\Container;
use Plumillon\MenuManager\MenuManager\Item;
use Plumillon\MenuManager\MenuManager\Menu;
use Plumillon\MenuManager\Listener\MenuManagerListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MenuManager {
	protected $app;
	protected $config = [];
	protected $menuList = [];
	protected $activeItem = null;
	protected $currentRoute = '';
	protected $currenParamList = [];
	protected $eventDispatcher;

	public function __construct(Container $app, $config) {
		$this->app = $app;
		$this->config = $config;
		$this->eventDispatcher = new EventDispatcher();
		
		foreach($config as $menuConfig)
			$this->menuList[] = new Menu($app, $menuConfig);
	}

	public function load($name = 'main', Item $item = null, $paramList = [], $activeOnly = false) {
		$menu = null;
		
		if($item != null)
			$menu = $this->findMenu($item->getSubList(), $name);
		else {
			if($this->activeItem != null) {
				$menu = $this->findMenu($this->activeItem->getSubList(), $name);
				
				if($menu == null)
					$menu = $this->findMenuReverse($this->activeItem, $name);
			}
			
			if($menu == null && !$activeOnly)
				$menu = $this->findMenu($this->menuList, $name);
		}
		
		/*
		 * // Be paranoid, menus should already be there
		 * if($menu == null) {
		 * $config = $this->findConfig($this->config, $name);
		 *
		 * if($config == null)
		 * throw new MenuManagerException('No ' . $name . ' menu config found');
		 *
		 * $config['params'] = $paramList;
		 * $menu = new Menu($this->app, $config);
		 * $this->menuList[] = $menu;
		 * } else
		 *
		 */
		
		if($menu != null) {
			if(!empty($paramList) && $menu->getParamList() != $paramList)
				$menu->setParamList($paramList);
			
			$menu->initActive();
		}

		$this->eventDispatcher->dispatch(MenuManagerListener::EVENT_MENU_LOADED);

		return $menu;
	}

	public function renderBreadcrumb($paramList = []) {
		$breadcrumb = '';
		
		if($this->activeItem != null)
			$breadcrumb = $this->activeItem->renderBreadcrumb($paramList);
		
		return $breadcrumb;
	}

	protected function findConfig($config, $name = 'main') {
		foreach($config as $menuConf)
			if(isset($menuConf['name']) && $menuConf['name'] == $name)
				return $menuConf;
			elseif(isset($menuConf['items'])) {
				$foundConfig = null;
				
				foreach($menuConf['items'] as $item) {
					if(isset($item['subs']))
						$foundConfig = $this->findConfig($item['subs'], $name);
					
					if($foundConfig != null)
						return $foundConfig;
				}
			}
		
		return null;
	}

	protected function findMenu(Array $menuList, $name = 'main') {
		foreach($menuList as $menu)
			if($menu->getName() == $name)
				return $menu;
			else {
				$foundMenu = null;
				
				foreach($menu->getItemList() as $item) {
					$foundMenu = $this->findMenu($item->getSubList(), $name);
					
					if($foundMenu != null)
						return $foundMenu;
				}
			}
		
		return null;
	}

	protected function findMenuReverse($item, $name = 'main') {
		$foundMenu = null;
		
		if($item != null)
			if($item instanceof Menu)
				$foundMenu = $this->findMenuReverse($item->getParent(), $name);
			else {
				$foundMenu = $this->findMenu($item->getSubList(), $name);
				
				if($foundMenu == null)
					$foundMenu = $this->findMenuReverse($item->getParent(), $name);
			}
		
		return $foundMenu;
	}

	public function render($name = 'main', Item $item = null, $paramList = [], $activeOnly = false) {
		$menu = $this->load($name, $item, $paramList, $activeOnly);
		
		if($menu != null)
			return $menu->render();
		
		return '';
	}

	public function setActiveItem(Item $item) {
		if($this->activeItem == null)
			$this->activeItem = $item;
		elseif($item->getDepth() >= $this->activeItem->getDepth()) {
			$this->activeItem->setActive(false);
			$this->activeItem = $item;
		}
	}

	public function getActiveItem() {
		return $this->activeItem;
	}

	public function setCurrentRoute($name) {
		$this->currentRoute = $name;
	}

	public function getCurrentRoute() {
		return $this->currentRoute;
	}

	public function setCurrentParamList($paramList) {
		$this->currenParamList = $paramList;
	}

	public function getCurrentParamList() {
		return $this->currenParamList;
	}

	public function addCurrentParam($key, $value) {
		$this->currenParamList[$key] = $value;
	}

	public function addCurrentParamList($paramList) {
		array_merge($this->currenParamList, $paramList);
	}

	public function addListener($listener, $event, $action = null) {
		if($listener instanceof MenuManagerListener)
			$this->eventDispatcher->addListener($event, [$listener, $action]);
		elseif($listener instanceof \Closure)
			$this->eventDispatcher->addListener($event, $listener);
	}
}