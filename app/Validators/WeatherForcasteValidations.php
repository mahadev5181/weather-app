<?php

namespace App\Validators;

class WeatherForcasteValidations extends Validator {

    static function weather($request) { 
        print_r($request);exit;
        static::validate($request, [
            'latitude' => 'required|integer',
            'longitude' => 'required|integer',
            'cnt'=>'optional|integer'
        ]);
    }
}

