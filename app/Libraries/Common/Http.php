<?php

namespace App\Libraries\Common;

use Illuminate\Support\Str;

class Http {

	const CONTENT_TYPE = [
		"json" => "application/json",
		"formData" => ""
	];

	protected $curl;
	protected $url;
	protected $method = "GET";
	protected $headers = [];

	protected $username = "";
	protected $password = "";
	protected $port = "";

	protected $proxy = false;

	protected $errorNumber = 0;
	protected $error = "";

	protected $errorForStatus = false;

	protected $responseHeaders = [];
    protected $params = [];

    private function __construct($method, $url, $params = [], $headers = [], $json = false, $timeout = 0) {

		$this->url = $url;
		$this->params = $params;
		$this->method = $method;
		$this->initialize($url, $params, $timeout);

		$this->headers($headers);
		if (!empty($json)) {
			$this->json($json);
		}

	}

	static function get($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("GET", $url, $params, $headers, $json, $timeout);
	}

	static function post($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("POST", $url, $params, $headers, $json, $timeout);
	}

	static function put($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("PUT", $url, $params, $headers, $json, $timeout);
	}

	static function patch($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("PATCH", $url, $params, $headers, $json, $timeout);
	}

	static function delete($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("DELETE", $url, $params, $headers, $json, $timeout);
	}

	static function options($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("OPTIONS", $url, $params, $headers, $json, $timeout);
	}

	static function head($url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http("HEAD", $url, $params, $headers, $json, $timeout);
	}

	static function request($method, $url, $params = [], $headers = [], $json = false, $timeout = 0) {
		return new Http(strtoupper($method), $url, $params, $headers, $json, $timeout);
	}

	/**
	 * Does a CUrl file upload
	 * file: [post_field_name: [file: /path/to/file, mimeType: image/jpg, name: test]]
	 * OR
	 * file: [post_field_name: /path/to/file]
	 */
	static function file($file, $url, $params = [], $headers = []) {

		if (empty($options["method"])) {
			$options["method"] = "POST";
		}

		return Http::request($options["method"], $url, $params, $headers)->upload($file);

	}

	function upload($file = false) {

		if (!empty($file)) {

			$this->requestShould(["allowBody" => true]);

			$files = [];
			foreach ($file as $name => $resource) {
				$files[$name] = $this->createFile($resource);
			}

			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $files);

		}

		return $this->fetch();

	}

	function json($data = []) {
		return $this->with(["json" => $data]);
	}

	function formData($data = []) {
		return $this->with(["formData" => $data]);
	}

	function encoded($data = []) {
		return $this->with(["encoded" => $data]);
	}

	function with($format = []) {

		if ($this->methodAllowsBody()) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->to($format));
		}

		return $this;

	}

	function headers($headers = []) {

		foreach ($headers as $key => $value) {
			$this->headers[ucfirst(strtolower($key))] = $value;
		}

		return $this;

	}

	function authorization($basic = "", $bearer = "", $username = "", $password = "") {

		if ($basic) {

			$basicAuth = $basic[0] ?? $basic;
			if (!empty($basic[1])) {
				$basicAuth = base64_encode("$basicAuth:".$basic[1]);
			}

			return $this->headers(["Authorization" => "Basic $basicAuth"]);

		}
		elseif ($bearer) {

			if (is_array($bearer)) {
				$bearer = base64_encode(json_encode($bearer));
			}

			return $this->headers(["Authorization" => "Bearer $bearer"]);

		}

		$this->username = $username;
		$this->password = $password;

		return $this;

	}

	function proxy($proxy) {

		$this->proxy = $proxy;
		return $this;

	}

	function do($errorForStatus = null) {

		$this->setup();
		$this->exceptionForStatus($errorForStatus);

		return $this->consumeResponse(curl_exec($this->curl));

	}

	function exceptionForStatus($status = false) {

		if ($status !== null) {
			$this->errorForStatus = $status;
		}
		return $this;

	}

	function curl() {

		$this->setup();
		return $this->curl;

	}

	function rawResponse() {
		return $this->response;
	}

	protected function initialize($url, $params, $timeout) {

		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		if ($this->isCustomRequest()) {
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->method);
		}
		elseif ($this->method == "POST") {
			curl_setopt($this->curl, CURLOPT_POST, 1);
		}

		if (!empty($this->proxy)) {
			curl_setopt($this->curl, CURLOPT_PROXY, $this->proxy);
		}

		if ($timeout > 0) {

			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
			curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout);

		}

	}

	protected function setup() {

		$this->setHeaders();
		curl_setopt($this->curl, CURLOPT_URL, $this->prepareUrl());

		$me = $this;
		curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function($curl, $header) use ($me) {

			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2) {
				return $len;
			}

			$me->responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
			return $len;

		});

	}

	protected function prepareUrl() {

		if (is_scalar($this->params)) {
			$this->params = [$this->params];
		}

		$urlComponents = parse_url($this->url);
		$preparedUrl = ($urlComponents["scheme"] ?? "https")."://";
		$urlComponents["username"] = $urlComponents["username"] ?? $this->username;
		if (!empty($urlComponents["username"])) {
			$preparedUrl .= $urlComponents["username"].":".($urlComponents["password"] ?? $this->password)."@";
		}
		$preparedUrl .= $urlComponents["host"] ?? "";
		$urlComponents["port"] = $urlComponents["port"] ?? $this->port;
		if (!empty($urlComponents["port"])) {
			$preparedUrl .= ":".$urlComponents["port"];
		}
		$preparedUrl .= $urlComponents["path"] ?? "";
		$queryParams = [];
		parse_str($urlComponents["query"] ?? "", $queryParams);
		$params = array_merge($this->params, $queryParams);
		if (!empty($params)) {
			$preparedUrl .= "?".http_build_query($params);
		}

		$urlComponents["fragment"] = $urlComponents["fragment"] ?? "";
		if (!empty($urlComponents["fragment"])) {
			$preparedUrl .= "#".$urlComponents["fragment"];
		}

		return $preparedUrl;

	}

	protected function methodAllowsBody() {
		return in_array($this->method, ["POST", "PUT", "PATCH"]);
	}

	protected function isCustomRequest() {
		return !in_array($this->method, ["GET", "POST"]);
	}

	protected function to($format) {

		if (isset($format["json"])) {
			return $this->toJson($format["json"]);
		}
		elseif (isset($format["encoded"])) {
			return http_build_query($format["encoded"]);
		}
		else if (isset($format["formData"])) {
			return $format["formData"];
		}
		else {
			return $format["raw"];
		}

	}

	protected function toJson($data) {

		$this->setContentType("json");
		return json_encode($data);

	}

	protected function toFormData($data) {

		$this->setContentType("formData");
		return http_build_query($data);

	}

	protected function setContentType($type) {

		if (empty($this->headers["Content-type"])) {
			$this->headers["Content-type"] = Http::CONTENT_TYPE[$type];
		}

	}

	protected function setHeaders() {

		$headersArr = [];
		foreach ($this->headers as $key => $value) {
			$headersArr[] = "$key: $value";
		}

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headersArr);

	}

	protected function requestShould($be, $make = "HEAD") {

		if (!empty($be["allowBody"]) && !$this->methodAllowsBody()) {

			$this->method = "POST";
			curl_setopt($this->curl, CURLOPT_POST, 1);

		}

		if (!empty($be["customRequest"]) && !$this->isCustomRequest()) {

			$this->method = $make;
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->method);

		}

	}

	protected function createFile($resource) {

		if (is_string($resource)) {
			$resource = ["file" => $resource];
		}

		return curl_file_create($resource["file"], $resource["mimeType"] ?? null, $resource["name"] ?? "");

	}

	protected function consumeResponse($response) {

		if (!empty($this->errorNumber = curl_errno($this->curl))) {

			$this->error = curl_error($this->curl);
			curl_close($this->curl);
			throw new Exceptions\HttpException($this->error, $this->errorNumber);

		}

		$httpResponse = new HttpResponse($this->curl, $this->responseHeaders, $response, $this->errorForStatus);
		curl_close($this->curl);

		return $httpResponse;

	}

	protected function multiConsume($multiCurl) {

		$response = curl_multi_getcontent($this->curl);
		curl_multi_remove_handle($multiCurl, $this->curl);
		return $this->consumeResponse($response);

	}

	static function parallel($http) {

		$multiCurl = curl_multi_init();
		foreach ($http as $httpObject) {
			curl_multi_add_handle($multiCurl, $httpObject->curl());
		}

		$running = null;
		do {
			curl_multi_exec($multiCurl, $running);
		} while ($running);

		$response = [];
		$exceptions = [];
		foreach ($http as $i => $httpObject) {

			try {
				$response[$i] = $httpObject->multiConsume($multiCurl);
			}
			catch (Throwable $ex) {

				$exceptions[$i] = $ex;
				if (is_a($ex, Exceptions\ParallelException::class)) {
					$response[$i] = $ex->response;
				}

			}

			curl_close($httpObject->curl());

		}

		curl_multi_close($multiCurl);

		if (!empty($exceptions)) {
			throw new Exceptions\ParallelException($response, $exceptions);
		}
		return $response;

	}

}
