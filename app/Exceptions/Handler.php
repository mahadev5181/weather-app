<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];
    /**
     * OneDirectException status map array
     *
     * @var array|int[]
     */

    protected $statusMap = [
        ModelNotFoundException::class => 404,
        ValidationException::class => 422,
        NotFoundHttpException::class => 404,
        MethodNotAllowedHttpException::class => 405
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request    $request
     * @param  \Throwable $eexception
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            $errors = $exception->errors();
            foreach($exception->errors() as $key => $val) {
                $errorResponse[] = ['key' => $key, 'value' => $val[0] ];
            }
            return response()->json(
                [
                    'code' => 400,
                    'message' => $exception->getMessage(),
                    'errors' => $errorResponse
                ],
                400
            );
        }
        // adding newrelic
        if (extension_loaded('newrelic')) {
            newrelic_notice_error($exception);
        }
        $rendered = parent::render($request, $exception);
        $code = (!empty($rendered->getStatusCode()) ? $rendered->getStatusCode() : 500);

        return response()->json(
            [
                'code' => $code,
                'message' => $this->getErrorMessage($code),
                'errors' => [
                    'details' => [
                        'code' => $rendered->getStatusCode(),
                        'message' => $this->getErrorMessage($code)
                    ]
                ]
            ],
            $code
        );
    }
    /**
     * @param  $status
     * @return mixed
     */
    protected function getErrorMessage($status): mixed
    {
        return match ($status) {
            500 => 'Internal Server Error',
            401 => 'Unauthorized',
            204 => 'No Content',
            404 => 'Page Not Found',
            405 => 'Method Not Allowed',
            400 => 'Bad Request',
            default => 'Internal Server Error',
        };
    }
}
