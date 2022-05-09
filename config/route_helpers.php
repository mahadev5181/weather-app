<?php

use Illuminate\Support\Str;

function resource($path, $controller = false, $options = []) {

	if (empty($controller)) {
		$controller = ucfirst(Str::camel($path))."Controller";
	}

	$router = app("router");

	$methods = getMethods($options);
	foreach ($methods as $action => $method) {
		$router->$method(...createRouteAndAction($method, $path, $controller, $action));
	}

}

function getMethods($options) {

	$methods = [
		"index" => "get",
		"show" => "get",
		"create" => "post",
		"update" => "put",
		"delete" => "delete"
	];

	if (!empty($options["only"])) {
		$methods = array_intersect_key($methods, array_flip($options["only"]));
	}
	elseif (!empty($options["except"])) {
		$methods = array_diff_key($methods, array_flip($options["except"]));
	}

	return $methods;

}

function createRouteAndAction($method, $path, $controller, $action) {

	if (in_array($action, ["show", "update", "delete"])) {
		$path .= "/{id}";
	}

	return [$path, $controller."@".$action];

}