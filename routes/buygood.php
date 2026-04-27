<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\DailyRevenueController;
use App\Http\Controllers\ShipmentsController;
use App\Http\Controllers\MerchantController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 梁社漢 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	/* 新品 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_NEW_RELEASE->value, ':')])->group(function(){
		Route::get('new_releases', [NewReleaseController::class, 'showSearch'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
		Route::get('new_releases/export/{token}', [NewReleaseController::class, 'export'])->name('new_releases.export');
	});
	
	/* 銷售報表 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_SALES->value, ':')])->group(function(){
		Route::get('sales', [SalesController::class, 'showSearch'])->name('sales');
		Route::post('sales/search', [SalesController::class, 'search'])->name('sales.search');
		Route::get('sales/export/{token}', [SalesController::class, 'export'])->name('sales.export');
	});
	
	/* 本日營收 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_DAILY_REVENUE->value, ':')])->group(function(){
		Route::get('daily_revenue', [DailyRevenueController::class, 'showSearch'])->name('daily_revenue');
		Route::post('daily_revenue/search', [DailyRevenueController::class, 'search'])->name('daily_revenue.search');
	});
	
	/* 出貨報表 */
	/*Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_PURCHASE->value, ':')])->group(function(){
		Route::get('purchase', [PurchaseController::class, 'showSearch'])->name('purchase');
		Route::post('purchase/search', [PurchaseController::class, 'search'])->name('purchase.search');
		Route::get('purchase/export/{token}', [PurchaseController::class, 'export'])->name('purchase.export');
	});*/
	
	/* 出貨總量查詢 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_SHIPMENTS->value, ':')])->group(function(){
		Route::get('shipments', [ShipmentsController::class, 'showSearch'])->name('shipments');
		Route::post('shipments/search', [ShipmentsController::class, 'search'])->name('shipments.search');
		Route::get('shipments/export/{token}', [ShipmentsController::class, 'export'])->name('shipments.export');
	});	
	
	/* 門店資訊 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_MERCHANT->value, ':')])->group(function(){
		Route::get('merchant', [MerchantController::class, 'showSearch'])->name('merchant');
		Route::post('merchant/search', [MerchantController::class, 'search'])->name('merchant.search');
		Route::get('merchant/export/{token}', [MerchantController::class, 'export'])->name('merchant.export');
	});	
});


