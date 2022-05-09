<?php

namespace App\Phystrix\WeatherForcastCommands;

use App\Phystrix\WeatherForcastBaseCommand;
use Exception;

/**
 * All commands must extends Phystrix's AbstractCommand
 */
class ForcastDataCommand extends WeatherForcastBaseCommand {
    protected $requestData;

	function __construct($requestData) {
		parent::__construct();
        $this->paramData = $requestData;
        $this->bearerToken = request()->bearerToken();
        $this->prepare();

	}

    protected function run() {
        
    }

    function getHttpObj() { 
        ///data/2.5/forecast?lat=35&lon=139&appid
        //data/2.5/forecast?id=524901&appid=858f15fed9292cbe25c341a754c55e45
        //https://api.openweathermap.org/data/2.5/weather?lat={lat}&lon={lon}&appid={API key}
        return $this->setHttp(
            method: 'get',
            url: 'data/2.5/'.$this->paramData,
            headers: []
        );
    }

    function getFallback(?Exception $exception = null) {

        return [
            "data" => $exception->getMessage(),
            "status" => 500
        ];

    }

}
