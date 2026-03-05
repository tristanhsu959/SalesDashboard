<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
		then: function () {
            Route::middleware('web')
                ->prefix('bafang')
                ->name('bafang.')
                ->group(base_path('routes/bafang.php'));
			
			Route::middleware('web')
                ->prefix('buygood')
                ->name('buygood.')
                ->group(base_path('routes/buygood.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
