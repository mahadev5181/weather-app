<?php

namespace App\Libraries\Common;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface {

	function get($id) {
		throw new NotFoundExceptionInterface("$id not found");
	}

	function has($id) {
		return false;
	}

}