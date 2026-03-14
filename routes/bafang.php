<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 八方 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	#name不用加prefix
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_NEW_RELEASE->value, ':')])->group(function(){
		/* 新品 */
		Route::get('new_releases', [NewReleaseController::class, 'showSearch'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
		Route::get('new_releases/export/{token}', [NewReleaseController::class, 'export'])->name('new_releases.export');
		
		/* 本日營收 */
		Route::get('today_sales', [NewReleaseController::class, 'export'])->name('today_sales');
	});
});
