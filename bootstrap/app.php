<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Auth\NoAuth;
use App\Http\Middleware\Auth\AuthMiddleware;
use App\Http\Middleware\Permission\CheckWebPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'noAuth' => NoAuth::class,
            'auth' => AuthMiddleware::class,
            'permission' => CheckWebPermission::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
