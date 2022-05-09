<?php

namespace App\Libraries\Common\Exceptions;

use Exception;

class HttpException extends Exception {

	public $response;
	public $code;

	function __construct($response, $code) {

		parent::__construct();

		$this->response = $response;
		$this->code = $code;

	}

}