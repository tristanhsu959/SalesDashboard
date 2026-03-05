<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 八方 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_NEW_RELEASE->value, ':')])->group(function(){
		Route::get('new_releases', [NewReleaseController::class, 'index'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
	});
});


