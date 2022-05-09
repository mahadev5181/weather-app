<?php

namespace App\Http\Controllers;

use App\Validators\Validator;
use App\Validators\TestValidator;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController {

	function call($command) {
		return app()->phystrix->getCommand(...func_get_args())->execute();
	}

	function query($command) {
		return app()->phystrix->getCommand(...func_get_args());
	}

	protected function json($res, $status = 200) {
		return response()->json($res, $status);
	}

	protected function success($res, $status = 200) {

		if (!is_array($res)) {
			$res = ["data" => $res];
		}

		return $this->json($this->jsonize($res), $status);

	}

	protected function error($error, $status = 400) {

		if (!is_array($error)) {
			$error = ["errors" => $error];
		}

		return $this->json($error, $status);

	}

	// function responseWithMessage($res, $message, $status = 200)
	// {
	// 	//if (!is_array($res)) {
	// 	$res = ["data" => $res, 'message' => $message, 'code' => $status];
	// 	//}
	// 	return $this->json($res);
	// }

	function responseWithMessage($message, $status, $code = null, $data = []) {

		$code = $code ?: $status;

		if ($status > 300) {
			return $this->json(["errors" => $data, "message" => $message, "code" => $code], $status);
		}

		return $this->json(["data" => $data, "message" => $message, "code" => $code], $status);

	}

	protected function response($result) {

		if ($result['status'] >= 200 && $result['status'] < 300) {
			return $this->success($result['data'], $result['status']);
		}

		return $this->error($result['data'], $result['status']);

	}

	protected function jsonize($response) {

		$res = [];
		foreach ($response as $key => $value) {

			if (is_a($value, Builder::class) || is_a($value, QueryBuilder::class)) {
				$value = $value->get();
			}

			$res[$key] = $value;

		}

		return $res;

	}

	protected function varDump($object) {
		return response("<pre>" . var_export($object, true) . "</pre>");
	}

    /**
     * Function for converting admin raw response into new response
     *
     * @auther Sagar Bele
     * @date   26-07-2021
     *
     * @param  $result  array
     * @param  $type    string
     * @param  $message string
     * @param  $code    string
     * @param  $data    array
     *
     * @return json
     */
    function transformResponse($result, $type = "", $message = "", $code = "", $data = []) {

        if ($type == "LMS") {
        	return $this->transformLMSResponse($result, $message, $code, $data);
        }

        $raw = $result['data']['raws'];
        $code = $code ?: $result['status'];

        $response = [
            "code" => $code,
            "message" => !empty($message) ? $message : $raw['success_message'] ?? $raw['error_message']
        ];

        if ($code == 200) {
            $response['data'] = !empty($data) ? $data : $raw['data'];
        }

        return $this->json($response, $code);

    }

    protected function transformLMSResponse($result, $message, $code, $data) {
    	return $this->json($result, $result["code"]);
    }


	/**
     * This method converts new rewards-api response into mobile-api/api-v1 response.
     *
     * @auther Bhupendra Pandey
     * @date   27-10-2021
     *
     * @param  $result  array
     * @param  $message string
     * @param  $code    string
     * @param  $data    array
     *
     * @return json
     */
    function convertRewardsResponse($result)
	{
		$response = [];
		$code = $result['status'];
		$response['code'] = $code;
		try{
			$data = $result['data']['data'] ?? '';
			$data = empty($data) ?  (object)[] : $data;
			$message = $result['data']['message'] ?? '';

			if ($code >= 200 && $code < 300) {
				$response['message'] = 'Data fetched successfully.';
				$response['data'] = $data;
			} else {
				$response['message'] = !empty($message) ? $message : 'An error occurred.';
				$response['errors'] = [];
			}
		}
		catch(Exception $e){
			$response['message'] = !empty($message) ? $message : 'An error occurred.';
			$response['errors'] = $e;
		}
		return $this->json($response, $code);
    }
}
