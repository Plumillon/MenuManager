<?php

namespace Plumillon\MenuManager\MenuManager;

use Pimple\Container;
use MenuManager;
use Plumillon\MenuManager\Exception\MenuManagerException;

abstract class MenuAbstract {
	protected $app;
	protected $parent;
	protected $template;
	protected $allowAllParams = false;
	protected $allowedParamList = [];
	protected $paramList = [];
	protected $isActive = false;
	protected $breadcrumbTemplate = '@MenuManager/breadcrumb.html.twig';
	protected $depth = 0;

	abstract function init(Array $optionList);
	abstract function initParamList();
	abstract function render();

	public function __construct(Container $app, Array $optionList, MenuAbstract $parent = null) {
		$this->app = $app;
		$this->initBulkConfig($optionList, ['template', 'itemTemplate', 'breadcrumbTemplate', 'allowAllParams', 'allowedParamList' => 'allowedParams']);

		if(isset($optionList['params']))
			$this->setParamList($optionList['params']);

		if($parent != null)
			$this->setParent($parent);

		$this->init($optionList);
	}

	protected function checkName(Array $optionList) {
		if(!isset($optionList['name']))
			throw new MenuManagerException('Menu must have a name in config');
	}

	public function setParent($parent) {
		$this->parent = $parent;

		if($this->parent != null)
			$this->depth = ($this->parent->getDepth() + 1);
	}

	public function getParent() {
		return $this->parent;
	}

	public function setParamList($paramList) {
		$this->paramList = $paramList;

		$this->initParamList();
	}
	
	public function getParamList() {
		return $this->paramList;
	}

	public function initConfig($optionList, $name, $objectName = null) {
		if($objectName == null)
			$objectName = $name;

		if(isset($optionList[$name]))
			$this->$objectName = $optionList[$name];
	}

	public function initBulkConfig($optionList, $bulk) {
		foreach($bulk as $key => $name)
			$this->initConfig($optionList, $name, (is_numeric($key) ? null : $key));
	}

	protected function getBreadcrumb() {
		return array_merge(($this instanceof Item ? [$this] : []), ($this->parent != null ? $this->parent->getBreadcrumb() : []));
	}

	public function setActive($active = false) {
		$this->isActive = ($this->isActive || $active);

		if($this->parent != null)
			$this->parent->setActive($active);
	}

	public function isActive() {
		return $this->isActive;
	}

	public function getDepth() {
		return $this->depth;
	}
}