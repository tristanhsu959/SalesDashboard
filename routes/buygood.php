<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesController;


use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 梁社漢 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_NEW_RELEASE->value, ':')])->group(function(){
		/* 新品 */
		Route::get('new_releases', [NewReleaseController::class, 'showSearch'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
		
		/* 銷售報表 */
		Route::get('sales', [SalesController::class, 'showSearch'])->name('sales');
		Route::post('sales/search', [SalesController::class, 'search'])->name('sales.search');
		Route::get('sales/export/{token}', [SalesController::class, 'export'])->name('sales.export');
		
		/* 出貨報表 */
		Route::get('purchase', [PurchaseController::class, 'showSearch'])->name('purchase');
		Route::post('purchase/search', [PurchaseController::class, 'search'])->name('purchase.search');
		Route::get('purchase/export/{token}', [PurchaseController::class, 'export'])->name('purchase.export');
	});
});


