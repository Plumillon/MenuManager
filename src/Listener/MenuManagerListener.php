<?php

namespace Plumillon\MenuManager\Listener;

use Symfony\Component\EventDispatcher\Event;
use Plumillon\MenuManager\MenuManager;

interface MenuManagerListener {
	const EVENT_MENU_LOADED = 'menu_manager.menu_loaded';
	const ACTION_MENU_LOADED = 'onMenuLoaded';

	public function onMenuLoaded(Event $event);
}