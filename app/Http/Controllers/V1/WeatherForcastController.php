<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Libraries\Common\Http;
use App\Libraries\Common\Log;
use App\Constants\Constants;
use App\Phystrix\WeatherForcastCommands\ForcastDataCommand;
use Exception;
use Illuminate\Http\Request;
use App\Validators\WeatherForcasteValidations;  
use App\Validators\Validator;

class WeatherForcastController extends Controller
{
    #[Validator(WeatherForcasteValidations::class, "weather")]
    public function getWeatherData(Request $request){
        $weatherParam = "weather?lat=".$request->get('latitude')."&lon=".$request->get('longitude');
        $weather = $this->weatherForcastAPI($request,$weatherParam);
        $apiResponse = Http::parallel([$weather]);
        $traceId = '12345';
    
        if ($apiResponse[0]->httpCode >= 200 && $apiResponse[0]->httpCode < 300) {
            $weatherAPIDetails = json_decode($apiResponse[0]->response);
                 
            return $this->responseWithMessage(data:$weatherAPIDetails, message:"Success", status:$apiResponse[0]->httpCode);
        } else {
            return $this->responseWithMessage(data:null,message:"Something went wrong", status:500);
        }
    }

    #[Validator(WeatherForcasteValidations::class, "weather")]
    public function getForcastData(Request $request){
        $forcastParam = "forecast?lat=".$request->get('latitude')."&lon=".$request->get('longitude');

        if(!empty($request->get('cnt'))){
            $forcastParam .= "&cnt=".$request->get('cnt');
        }
        
        $weather = $this->weatherForcastAPI($request,$forcastParam);
        $apiResponse = Http::parallel([$weather]);
        $traceId = '12345';

        $responseDecode = $this->decodeResponse($traceId, $apiResponse);

        if ($responseDecode['code'] >= 200 && $responseDecode['code'] < 300) {
            [$forcasteAPIDetails] = [...$responseDecode['data']];
            [$forcasteAPIMessage] = [...$responseDecode['message']];

            return $this->responseWithMessage(data:$forcasteAPIDetails, message:$forcasteAPIMessage, status:$responseDecode['code']);
        } else {
            return $this->responseWithMessage(data:null,message:"Something went wrong", status:$responseDecode['code']);
        }
    }

    protected function weatherForcastAPI(Request $request,$baseParam)
    {
        $apiKey = Constants::WEATHERFORCASTAPIKEY;
        $paramData = $baseParam."&appid=".$apiKey;
        
        $weatherForcastCommand = new ForcastDataCommand($paramData);
        return $weatherForcastCommand->getHttpObj();
    }

    public function decodeResponse($traceId, $data){
        try {
            $respArr = $resMessage = [];
            foreach ($data as $res) {
                $httpCode = $res->httpCode;
                if ($res->success) {
                    $transferInfo = $res->transferInfo;
                    $res = $res->response;

                    $decodedRes = json_decode($res, true);

                    if (empty($decodedRes['code'])) {
                        $decodedRes['code'] = $httpCode;
                    }
                    if ((isset($decodedRes['code'])) && in_array($decodedRes['code'], [200, 201, 202, 203])) {
                        Log::info($traceId, [$decodedRes, $transferInfo['url']]);
                        $respArr[] = $decodedRes['list'];
                        $resMessage[] = $decodedRes['message']; 
                    } 
                } else {
                    $transferInfo = $res->transferInfo;
                    $res = $res->response;
                    $decodedRes = json_decode($res, true);
                    if($decodedRes == null) {
                        return ['errors' => [], 'message' => $res->response, 'code' => !empty($res->httpCode) ? $res->httpCode : 400];
                    }
                    Log::error($traceId, [$res, $transferInfo['url']]);

                    if(!empty($decodedRes['error'])){
                        $decodedRes['errors'] = $decodedRes['error'];
                    }
                    return ['errors' => !empty($decodedRes['errors']) ? $decodedRes['errors'] : "", 'message' => $decodedRes['message'] ?? null, 'code' => $decodedRes['code'] ?? $httpCode];
                }
            }
            return ['data' => $respArr, 'message' => $resMessage, 'code' => 200];
        } catch (Exception $e) {
            Log::error($traceId, [$e->getMessage()]);
            return ['message' => $e->getMessage(), 'code' => !empty($e->getCode()) ? $e->getCode() : 500];
        }
    }
}
