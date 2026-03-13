<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (NotFoundHttpException $e) {
            return ApiResponse::error('Recurso não encontrado.', 404);
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e) {
            return ApiResponse::error('Método HTTP não permitido.', 405);
        });

        $exceptions->renderable(function (TooManyRequestsHttpException $e) {
            return ApiResponse::error('Muitas requisições. Tente novamente em instantes.', 429);
        });
    })->create();