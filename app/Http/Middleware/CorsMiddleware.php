<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    function handle($request, Closure $next) {

        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET, OPTIONS, PUT, DELETE,POST ',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '3600',
            'Access-Control-Allow-Headers'     => 'Authorization, Origin, Content-Type, Accept, x-mpkt-request-id, x-mpkt-user'
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json(["method"=> "OPTIONS"], 200, $headers);
        }

        $response = $next($request);

        if (!($response instanceof Response)) {
            return $response;
        }

        foreach($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;

    }

}
