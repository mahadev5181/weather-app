<?php

namespace App\Libraries\Common;

use Throwable;
use Illuminate\Support\Facades\Request;

class Log {

	const LEVELS = [
		"debug",
		"info",
		"warn",
		"error",
		"critical"
	];

	static function __callStatic($method, $args = []) {

		if (!in_array($method, Log::LEVELS)) {

			Log::log("error", "Log@__callStatic", "", ["error" => "Invalid log method $method"]);
			throw new \BadMethodCallException("Log::$method is not a function");

		}

		$log = Log::getSource();
		Log::log($method, $log, ...$args);

	}

	static function log($level, $source, $traceId = "", $message = []) {

		if (!Log::allowedLevel($level)) {
			return;
		}

		if (is_a($message, Throwable::class)) {
			$message = $message."";
		}

		if (!is_scalar($message)) {
			$message = json_encode($message);
		}

		$level = strtoupper($level);
		$ip = Request::ip();
		$filename = env("APP_NAME");

		$log = "~# ".date("Y-m-d\TH:i:sT")."|$filename|$source|$traceId|$level|$ip|$message\n";
		error_log($log, 3, storage_path("logs/$filename-".date("Y_m_d").".log"));

	}

	static function getSource() {

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		return $trace[2]["class"]."@".$trace[2]["function"];

	}

	protected static function allowedLevel($level) {

		$allowedLogLevelIndex = array_search(env("LOG_LEVEL", "info"), Log::LEVELS);
		$levelIndex = array_search($level, Log::LEVELS);

		return $allowedLogLevelIndex <= $levelIndex;

	}

}
