<?php

namespace App\Libraries\Common;

class HttpResponse implements \JsonSerializable {

	public $httpCode;
	public $success;
	public $headers = [];
	public $response;
	public $json;
	protected $curl;
	protected $errorForStatus;
	public $transferInfo;

	function __construct($curl, $headers, $response, $errorForStatus) {

		$this->curl = $curl;
		$this->headers = $headers;
		$this->response = $response;
		$this->errorForStatus = $errorForStatus;
		$this->transferInfo = curl_getinfo($this->curl);
		$this->httpCode = $this->transferInfo["http_code"];
		$this->success = intval($this->httpCode / 100) == 2;
		$this->parse();
		$this->errorForStatus();

	}

	protected function parse() {

		// Try json decoding
		$this->json = json_decode($this->response, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->json = $this->response;
		}

	}

	protected function errorForStatus() {

		if ($this->errorForStatus && $this->hasError()) {
			throw new Exceptions\HttpException($this, $this->httpCode);
		}

	}

	function jsonSerialize() {
		return $this->response;
	}

	function __toString() {
		return $this->response;
	}

	function __get($attribute) {

		if ($attribute == "transferInfo") {
			return $this->transferInfo;
		}

		$key = Str::snake($attribute);
		if (!empty($this->transferInfo[$key])) {
			return $this->transferInfo[$key];
		}

		throw new \BadMethodCallException("Call to undefined attribute ".static::class."::$key");

	}

	protected function hasError($code = false) {

		if ($this->success) {
			return false;
		}

		if ($code) {

			$httpCode = intval($this->httpCode / 100);
			return $code == $httpCode || $code == $this->httpCode;

		}

		if (is_array($this->errorForStatus)) {

			foreach ($this->errorForStatus as $status) {

				if ($this->hasError($status)) {
					return true;
				}

			}

		}
		else {
			return $this->hasError(is_int($this->errorForStatus) ? $this->errorForStatus : $this->httpCode);
		}

	}

	function __debugInfo() {

		return [
			"response" => $this->response,
			"httpCode" => $this->httpCode
		];

	}

}