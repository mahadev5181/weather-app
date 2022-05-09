<?php

namespace App\Http\Middleware;

use Closure;

class RequestIdMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $except = [
                            '/',
                            'v1/weather',
                            'v1/forcast'
                        ];
    public function handle($request, Closure $next) {

        if (in_array($request->path(), $this->except)) {
            return $next($request);
        }

        if (!$this->getRequestId($request)) {
            return response(["error" => "Request ID not found"], 400);
        }

        return $next($request);

    }

    protected function getRequestId($request) {
        $header = $request->header("x-mpkt-request-id");
        return $request->requestId = $header ?: (
            env("APP_ENV") == "local" ? "1".time() : null);

    }

}
