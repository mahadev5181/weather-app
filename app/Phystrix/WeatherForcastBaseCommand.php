<?php

namespace App\Phystrix;

use App\Phystrix\BaseCommand;

/**
 * All commands must extends Phystrix's AbstractCommand
 */
abstract class WeatherForcastBaseCommand extends BaseCommand {

	function prepare() {
		$this->baseUrl = env("WEATHER_API_BASE_URL");
	}

}
