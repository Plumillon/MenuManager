<?php

namespace Plumillon\MenuManager\MenuManager;

use Pimple\Container;

class Menu extends MenuAbstract {
	public $name = 'main';
	protected $template = '@MenuManager/default.html.twig';
	protected $itemTemplate = '@MenuManager/item.html.twig';
	protected $itemList = [];

	public function init(Array $optionList) {
		$this->checkName($optionList);
		$this->name = $optionList['name'];
		
		if(isset($optionList['items']) && is_array($optionList['items']))
			foreach($optionList['items'] as $item) {
				if(!isset($item['template']))
					$item['template'] = $this->itemTemplate;

				if(!isset($item['breadcrumbTemplate']))
					$item['breadcrumbTemplate'] = $this->breadcrumbTemplate;

				if(isset($optionList['params']))
					$item['params'] = $optionList['params'];

				$itemAdd = new Item($this->app, $item, $this);
				$this->itemList[] = $itemAdd;
			}
	}

	public function initParamList() {
		foreach($this->itemList as $item)
			$item->setParamList($this->paramList);
	}

	public function initActive() {
		foreach($this->itemList as $item)
			$item->initActive();
	}

	public function render() {
		$renderedItemList = [];
		
		foreach($this->itemList as $item)
			$renderedItemList[] = $item->render();

		return $this->app['twig']->render($this->template, [
			'isActive' => $this->isActive,
			'itemList' => $renderedItemList
		]);
	}

	public function getName() {
		return $this->name;
	}

	public function getItemList() {
		return $this->itemList;
	}
}