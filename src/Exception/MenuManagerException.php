<?php

namespace Plumillon\MenuManager\Exception;

use Exception;

class MenuManagerException extends Exception {

	public function __construct($message, $code = 0) {
		parent::__construct($message, $code);
	}
}