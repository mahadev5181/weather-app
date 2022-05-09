<?php

namespace App\Phystrix;

use App\Libraries\Common\Http;
use App\Libraries\Common\Log;
use Exception;
use Odesk\Phystrix\AbstractCommand;

abstract class BaseCommand extends AbstractCommand
{
    protected $user;
    protected $requestId;
    protected $baseUrl = '';
    protected $auth = [];
    protected $bearerToken = '';

    public function __construct($auth = [])
    {
        $this->user = request()->user;
        $this->requestId = request()->requestId;
    }

    public function get($url, $params = [])
    {
        return $this->request("get", $url, $params);
    }

    public function post($url, $params = [], $json = false)
    {
        return $this->request("post", $url, $params, $json);
    }

    public function put($url, $params = [], $json = false)
    {
        return $this->request("put", $url, $params, $json);
    }

    public function patch($url, $params = [], $json = false)
    {
        return $this->request("patch", $url, $params, $json);
    }

    public function delete($url, $params = [])
    {
        return $this->request("delete", $url, $params);
    }

    public function request($method, $url, $params = [], $json = false, $timeout = 0)
    {
        if(!empty($this->auth)) {
            $response = Http::{$method}($this->baseUrl . $url, $params, $this->headers(), $json, $timeout)
            ->authorization(username: $this->auth[0], password: $this->auth[1])->do(5);
        }
        else if($this->bearerToken) {
            $headers = [ "Authorization" => "Bearer".$this->bearerToken];
            $response = Http::{$method}($this->baseUrl . $url, $params, $this->headers($headers), $json, $timeout)->do(5);

        }
        else {
            $response = Http::{$method}($this->baseUrl . $url, $params, $this->headers(), $json, $timeout)->do(5);
        }
        return [
            "data" => $response->json,
            "status" => $response->httpCode,
        ];
    }

    public function setHttp($method, $url, $params = [], $json = false, $headers = [], $timeout = 0)
    {
        return $this->buildHttp($method, $url, $params, $json, $headers, $timeout)
            ->authorization(username: env('API_LUMEN_USER'), password: env('API_LUMEN_PASS'));
    }

    public function auth()
    {
        return []/*["basic" => []]*/;
    }

    public function headers($headers = []): array
    {
        return array_merge($headers, [
            'X-MPKT-USER' => urlencode(base64_encode(json_encode($this->user))),
            'X-MPKT-REQUEST-ID' => $this->requestId,
        ]);
    }

    protected function getFallback(Exception $exception = null)
    {
        Log::error($this->requestId, $exception?->getMessage() ?: "Fallback invoked");
        return [
            "errors" => [],
            "message" => "Something went wrong...",
            "status" => 500,
        ];
    }

    protected function getCacheKey()
    {
        return static::class;
    }

    public function buildHttp($method, $url, $params = [], $json = false, $headers = [], $timeout = 0)
    {
        return Http::{$method}($this->baseUrl . $url, $params, $headers, $json, $timeout)
            ->exceptionForStatus(5);
    }
}
