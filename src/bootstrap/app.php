<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::failed(
                message: $e->getMessage(),
                errors: $e->errors(),
                status: 422,
            );
        });

        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            return ApiResponse::error(message: 'Unauthenticated.', status: 401);
        });

        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*') || $request->is('api/documentation*') || $request->is('api/oauth2-callback*')) {
                return null;
            }

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong.';

            return ApiResponse::error(message: $message, status: 500);
        });
    })->create();
