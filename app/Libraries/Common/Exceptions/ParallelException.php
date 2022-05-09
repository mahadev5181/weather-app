<?php

namespace App\Libraries\Common\Exceptions;

use Exception;

class ParallelException extends Exception {

	public $responses;
	public $exceptions;

	function __construct($responses, $exceptions) {

		parent::__construct();

		$this->responses = $responses;
		$this->exceptions = $exceptions;

	}

}