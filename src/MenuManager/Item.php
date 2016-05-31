<?php
namespace Plumillon\MenuManager\MenuManager;

use Pimple\Container;
use Plumillon\MenuManager\Exception\MenuManagerException;

class Item extends MenuAbstract
{
    protected $path;
    protected $url;
    protected $text;
    protected $trans;
    protected $display = true;
    protected $displayBreadcrumb = true;
    protected $subList = [];

    public function init(Array $optionList)
    {
        $this->initBulkConfig($optionList, [
            'url',
            'text',
            'trans',
            'path',
            'display',
            'displayBreadcrumb'
        ]);
        
        if (isset($optionList['subs']) && is_array($optionList['subs']))
            foreach ($optionList['subs'] as $subMenuConfig) {
                if (! isset($subMenuConfig['breadcrumbTemplate']))
                    $subMenuConfig['breadcrumbTemplate'] = $this->breadcrumbTemplate;
                
                if (isset($optionList['params']))
                    $subMenuConfig['params'] = $optionList['params'];
                
                $subMenu = new Menu($this->app, $subMenuConfig, $this);
                $this->subList[] = $subMenu;
            }
        
        if ($this->trans != null)
            $this->setTrans($this->trans);
    }

    public function initParamList()
    {
        foreach ($this->subList as $subMenu)
            $subMenu->setParamList($this->paramList);
    }

    public function render()
    {
        if ($this->display) {
            $this->paramList = $this->getAllowedParamList($this->paramList);
            
            if ($this->path != null) {
                // Override given URL with the corresponding path
                $this->url = $this->app['url_generator']->generate($this->path, $this->paramList);
            } else {
                if (! empty($this->paramList)) {
                    $urlParamList = $this->paramList;
                    array_walk($urlParamList, function (&$item, $key) {
                        $item = $key . '=' . $item;
                    });
                    $urlParam = implode('&', $urlParamList);
                    $this->url .= '?' . $urlParam;
                }
            }
            
            $this->display = $this->app['menu_manager.security']->isGranted($this->url);
            
            if ($this->display)
                return $this->app['twig']->render($this->template, [
                    'item' => $this
                ]);
        }
        
        return '';
    }

    public function initActive()
    {
        $currentParamList = array_merge($this->app['request']->attributes->get('_route_params'), $this->app['request']->query->all(), $this->app['menu_manager']->getCurrentParamList());
        $potentialParamList = $this->getAllowedParamList($currentParamList);
        
        // var_dump($this->app['request']->getRequestUri());
        $this->setActive(isset($this->path) && $this->app['menu_manager']->getCurrentRoute() == $this->path && $potentialParamList == $currentParamList);
        
        foreach ($this->subList as $subMenu)
            $subMenu->initActive();
        
        if ($this->isActive)
            $this->app['menu_manager']->setActiveItem($this);
    }

    public function renderBreadcrumb($paramList = [])
    {
        $breadcrumbList = [];

        foreach ($this->getBreadcrumb() as $breadcrumb)
            if ($breadcrumb->display) {
                $breadcrumb->paramList = $breadcrumb->getAllowedParamList($paramList);
                
                if ($breadcrumb->path != null)
                    // Override given URL with the corresponding path
                    $breadcrumb->url = $this->app['url_generator']->generate($breadcrumb->path, $breadcrumb->paramList);

                $breadcrumbList[] = $breadcrumb;
            }
        
        return $this->app['twig']->render($this->breadcrumbTemplate, [
            'breadcrumbList' => array_reverse($breadcrumbList),
            'breadcrumbLength' => $this->breadcrumbLength
        ]);
    }

    private function getAllowedParamList($paramList = [])
    {
        $paramList = array_merge($paramList, $this->app['menu_manager']->getCurrentParamList());
        
        if ($this->allowAllParams)
            return $paramList;
        
        if ($this->path != null) {
            $urlParamList = array_merge($this->app['request']->attributes->get('_route_params'), $this->app['request']->query->all());
            $route = ($this->app['routes'] != null ? $this->app['routes']->get($this->path) : null);
            
            if ($route != null) {
                // Allowed params
                $routeParamList = $route->compile()->getVariables();
                $routeParamList = array_merge($routeParamList, $this->allowedParamList);
                
                if (empty($routeParamList))
                    $paramList = [];
                else
                    foreach ($paramList as $key => $param)
                        if (! in_array($key, $routeParamList))
                            unset($paramList[$key]);
                    
                    // Get param from URL if one is missing
                $notFoundList = array_diff($routeParamList, array_keys($paramList));
                
                foreach ($notFoundList as $notFound)
                    if (isset($urlParamList[$notFound]))
                        $paramList[$notFound] = $urlParamList[$notFound];
            } else
                throw new MenuManagerException('No route found for ' . $this->path);
        } else
            foreach ($paramList as $key => $param)
                if (! in_array($key, $this->allowedParamList))
                    unset($paramList[$key]);
        
        return $paramList;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getSubList()
    {
        return $this->subList;
    }

    public function setTrans($trans)
    {
        list ($trad, $domain, $locale) = array_merge(array_pad(array_reverse(explode('-', $trans)), 3, null));
        
        $this->text = $this->app['translator']->trans($trad, [], $domain, $locale);
    }
}