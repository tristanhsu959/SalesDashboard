<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
		#Define不同route
		then: function () {
            Route::middleware(['web', 'access_permission'])
                ->prefix('bf')    
                ->name('bf.')      
                ->group(base_path('routes/bafang.php'));
				
			Route::middleware('web')
                ->prefix('bg')    
                ->name('bg.')      
                ->group(base_path('routes/buygood.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
			'access_permission' => \App\Http\Middleware\AccessPermissionMiddleware::class,
		]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
